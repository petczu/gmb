<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AutoReplyQueueItem;
use App\Models\AutoReplyRule;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Reviews\ReviewProviderFactory;
use Throwable;

/**
 * Orchestrates AI auto-replies: match a per-star rule, check + debit credits,
 * generate text, and either publish (auto mode) or queue for approval (draft).
 *
 * Tenant-context methods (matching rules, reviews, queue) assume tenancy is
 * already initialized for $workspace. Credits are central via [[AiCreditService]].
 */
class AutoReplyService
{
    public function __construct(
        private readonly ReplyGenerator $generator,
        private readonly AiCreditService $credits,
        private readonly ReviewProviderFactory $providers,
        private readonly \App\Services\Billing\AiUsageService $usage,
    ) {}

    /**
     * Generate (and possibly publish) an auto-reply for one review.
     * Returns null when no enabled rule matches or the review is already replied.
     */
    public function generateForReview(Workspace $workspace, Review $review): ?AutoReplyQueueItem
    {
        if ($review->reply_text !== null) {
            return null;
        }

        $rule = $this->matchRule($review);
        if ($rule === null) {
            return null;
        }

        $cost = (int) config('services.ai.reply_credits', 1);

        if (! $this->usage->canAutoReply($workspace)) {
            $this->usage->notifyLimitReachedOnce($workspace);

            return AutoReplyQueueItem::create([
                'review_id' => $review->id,
                'generated_text' => '',
                'status' => 'skipped',
                'mode' => $rule->mode,
                'error' => 'ai_reply_cap_reached',
            ]);
        }

        try {
            $generated = $this->generator->generate(
                reviewText: (string) ($review->originalText() ?? $review->text),
                rating: (int) $review->rating,
                authorName: $review->author_name,
                businessName: (string) ($review->location?->name ?? 'our business'),
                tone: $rule->tone,
                instruction: $rule->instruction,
                language: $rule->language,
            );
        } catch (Throwable $e) {
            return AutoReplyQueueItem::create([
                'review_id' => $review->id,
                'generated_text' => '',
                'status' => 'failed',
                'mode' => $rule->mode,
                'error' => $e->getMessage(),
            ]);
        }

        // Log the AI call (model, tokens, real USD cost) after a successful
        // generation. Plan-included usage is delta 0; once the plan allowance is
        // exhausted the reply is served from purchased credits, so the same row
        // also debits the balance (decided before logging).
        $creditDelta = $this->usage->isServedFromCredits($workspace) ? -$cost : 0;
        $this->credits->logUsage($workspace, 'auto_reply', $generated->model, $generated->inputTokens, $generated->outputTokens, $creditDelta, 'review', (string) $review->id);

        $item = AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => $generated->text,
            'status' => $rule->mode === 'auto' ? 'published' : 'pending',
            'mode' => $rule->mode,
            'model' => $generated->model,
            'credits_spent' => $cost,
        ]);

        if ($rule->mode === 'auto') {
            $this->publish($workspace, $review, $generated->text, 'ai_auto');
            $item->forceFill(['decided_at' => now()])->save();
        }

        return $item;
    }

    /**
     * Approve a pending draft: publish it and mark the queue item.
     */
    public function approve(Workspace $workspace, AutoReplyQueueItem $item, ?int $userId): void
    {
        $review = $item->review;
        $this->publish($workspace, $review, $item->generated_text, 'ai_draft');

        $item->forceFill([
            'status' => 'published',
            'decided_by' => $userId,
            'decided_at' => now(),
        ])->save();
    }

    public function reject(AutoReplyQueueItem $item, ?int $userId): void
    {
        $item->forceFill([
            'status' => 'skipped',
            'decided_by' => $userId,
            'decided_at' => now(),
        ])->save();
    }

    /**
     * Generate auto-replies for all unreplied reviews without an active queue item.
     *
     * @return array{generated:int, published:int, queued:int, skipped:int}
     */
    public function processWorkspace(Workspace $workspace): array
    {
        $stats = ['generated' => 0, 'published' => 0, 'queued' => 0, 'skipped' => 0];

        Review::query()
            ->whereNull('reply_text')
            ->whereDoesntHave('queueItems', fn ($q) => $q->whereIn('status', ['pending', 'published']))
            ->with('location')
            ->each(function (Review $review) use ($workspace, &$stats): void {
                $item = $this->generateForReview($workspace, $review);
                if ($item === null) {
                    return;
                }
                $stats['generated']++;
                match ($item->status) {
                    'published' => $stats['published']++,
                    'pending' => $stats['queued']++,
                    default => $stats['skipped']++,
                };
            });

        return $stats;
    }

    private function matchRule(Review $review): ?AutoReplyRule
    {
        return AutoReplyRule::query()
            ->where('rating', $review->rating)
            ->where('enabled', true)
            ->where(fn ($q) => $q->where('location_id', $review->location_id)->orWhereNull('location_id'))
            // Per-location override (non-null location_id) wins over workspace-wide.
            ->orderByRaw('location_id IS NULL')
            ->first();
    }

    private function publish(Workspace $workspace, Review $review, string $text, string $source): void
    {
        $provider = $this->providers->make();
        $accountId = $review->location?->zernio_account_id ?? 'fake-account';

        $provider->reply($accountId, $review->external_review_id, $text);

        $review->forceFill([
            'reply_text' => $text,
            'replied_at' => now(),
            'reply_status' => 'published',
            'reply_source' => $source,
        ])->save();
    }
}
