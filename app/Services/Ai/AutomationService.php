<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Mail\ApprovalsPendingMail;
use App\Models\Automation;
use App\Models\AutoReplyQueueItem;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Billing\AiUsageService;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reviews\ReviewProviderFactory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs review-reply automations: match an enabled automation to a review,
 * produce the reply (a fixed default message OR an AI agent), and either
 * auto-publish it or queue it for approval. Reuses the AI credit ledger and the
 * Approvals queue ([[AutoReplyQueueItem]]).
 *
 * Tenant-context methods assume tenancy is already initialized for $workspace.
 */
class AutomationService
{
    public function __construct(
        private readonly ReplyGenerator $generator,
        private readonly AiCreditService $credits,
        private readonly ReviewProviderFactory $providers,
        private readonly AiUsageService $usage,
        private readonly ReplyScheduler $scheduler,
    ) {}

    public function matching(Review $review): ?Automation
    {
        // Deterministic overlap resolution: automations limited to specific
        // locations act as overrides and win over "All locations" catch-alls
        // (same pattern as AutoReplyRule); among equal scopes the older one
        // runs. A review is always handled by exactly one automation.
        return Automation::query()
            ->where('enabled', true)
            ->where('trigger', 'new_review')
            ->orderBy('all_locations')
            ->orderBy('id')
            ->get()
            ->first(fn (Automation $a): bool => $a->matches($review));
    }

    /**
     * Generate (and auto-publish or queue) a reply for one review, picking the
     * first matching automation. Used by the new-review hook.
     */
    public function processReview(Workspace $workspace, Review $review): ?AutoReplyQueueItem
    {
        if ($review->reply_text !== null) {
            return null;
        }

        $automation = $this->matching($review);
        if ($automation === null) {
            return null;
        }

        return $this->applyAutomation($workspace, $review, $automation);
    }

    /**
     * Apply ONE specific automation to a review (no matching-order resolution).
     */
    public function applyAutomation(Workspace $workspace, Review $review, Automation $automation): ?AutoReplyQueueItem
    {
        if ($review->reply_text !== null) {
            return null;
        }

        $usesAi = $automation->content_type === 'ai_agent';
        $cost = $usesAi ? (int) config('services.ai.reply_credits', 1) : 0;
        $model = null;
        $agentId = null;

        if ($usesAi) {
            $agent = $automation->aiAgent;
            if ($agent === null) {
                return $this->record($review, '', 'failed', 'draft', error: 'no_agent_configured');
            }

            // Plan's monthly AI auto-reply allowance (the internal meter behind
            // the tier limits — no customer-facing credits).
            if (! $this->usage->canAutoReply($workspace)) {
                $this->usage->notifyLimitReachedOnce($workspace);

                return $this->record($review, '', 'skipped', 'draft', error: 'ai_reply_cap_reached');
            }

            try {
                $generated = $this->generator->generate(
                    reviewText: (string) ($review->originalText() ?? $review->text),
                    rating: (int) $review->rating,
                    authorName: $review->author_name,
                    businessName: (string) ($review->location?->name ?? 'our business'),
                    tone: $agent->tone,
                    instruction: $agent->instructions(),
                    language: $agent->reply_native_language ? null : 'English',
                );
            } catch (Throwable $e) {
                return $this->record($review, '', 'failed', 'draft', error: $e->getMessage());
            }

            // Plan-included usage is delta 0; once the plan allowance is
            // exhausted the reply is served from purchased credits, so the same
            // ledger row also debits the balance.
            $creditDelta = $this->usage->isServedFromCredits($workspace) ? -$cost : 0;
            $this->credits->logUsage($workspace, 'auto_reply', $generated->model, $generated->inputTokens, $generated->outputTokens, $creditDelta, 'review', (string) $review->id);
            $text = $generated->text;
            $model = $generated->model;
            $agentId = (int) $agent->id;
        } else {
            $text = trim((string) $automation->default_message);
            if ($text === '') {
                return null;
            }
        }

        // Approval path is unchanged: queue for a human decision.
        if ($automation->approve_before_posting) {
            return $this->record($review, $text, 'pending', 'draft', model: $model, credits: $cost, agentId: $agentId);
        }

        // Auto path: schedule the post for an "organic" time. A zero delay with
        // no working-hours constraint resolves to now (or earlier) → publish now,
        // preserving the previous instant-publish behaviour.
        $postAt = $this->scheduler->scheduleFor($automation, now(), $this->timezoneFor($workspace, $review));
        $source = $usesAi ? 'ai_auto' : 'manual';

        if ($postAt->lessThanOrEqualTo(now())) {
            $item = $this->record($review, $text, 'published', 'auto', model: $model, credits: $cost, agentId: $agentId);
            $this->publish($review, $text, $source);
            $item->forceFill(['decided_at' => now()])->save();

            return $item;
        }

        // Defer: generation + credits already happened, only the POSTING waits.
        return $this->record(
            $review,
            $text,
            'scheduled',
            'auto',
            model: $model,
            credits: $cost,
            postAt: $postAt,
            agentId: $agentId,
        );
    }

    /**
     * Timezone the reply's working hours are interpreted in: the review's own
     * LOCATION timezone (so a multi-city workspace schedules each reply in the
     * right local time), falling back to the workspace timezone, then UTC.
     */
    public function timezoneFor(Workspace $workspace, Review $review): string
    {
        $locationTz = $review->location?->timezone;
        if (is_string($locationTz) && $locationTz !== '') {
            return $locationTz;
        }

        return $this->workspaceTimezone($workspace);
    }

    /** Workspace timezone (stored in the tenant `data` JSON), UTC fallback. */
    private function workspaceTimezone(Workspace $workspace): string
    {
        $tz = $workspace->timezone ?? null;

        return is_string($tz) && $tz !== '' ? $tz : (string) config('app.timezone', 'UTC');
    }

    /**
     * Run ONE automation across all eligible reviews it matches, optionally
     * limited to a review-date window (the manual "Run now" period scoping).
     *
     * @return array{generated:int, published:int, queued:int, skipped:int}
     */
    public function processAutomation(Workspace $workspace, Automation $automation, ?CarbonInterface $from = null, ?CarbonInterface $until = null): array
    {
        $stats = ['generated' => 0, 'published' => 0, 'queued' => 0, 'skipped' => 0];

        if (! $automation->enabled) {
            return $stats;
        }

        $this->eligibleReviews($from, $until)
            ->each(function (Review $review) use ($workspace, $automation, &$stats): void {
                if (! $automation->matches($review)) {
                    return;
                }
                $this->tally($this->applyAutomation($workspace, $review, $automation), $stats);
            });

        return $stats;
    }

    /**
     * Run automations across the workspace's eligible reviews. $from limits by
     * REVIEW date — the scheduled safety-net run passes a recency window so a
     * freshly connected location's multi-year backlog is never mass-replied.
     *
     * @return array{generated:int, published:int, queued:int, skipped:int}
     */
    public function processWorkspace(Workspace $workspace, ?CarbonInterface $from = null): array
    {
        $stats = ['generated' => 0, 'published' => 0, 'queued' => 0, 'skipped' => 0];

        $this->eligibleReviews($from)
            ->each(function (Review $review) use ($workspace, &$stats): void {
                $this->tally($this->processReview($workspace, $review), $stats);
            });

        if ($stats['queued'] > 0) {
            $this->notifyPendingApprovals($workspace);
        }

        return $stats;
    }

    /**
     * Email the workspace owner that replies are waiting for approval, throttled
     * to once per hour per workspace (a fresh backlog must not spam). The daily
     * ApprovalsReminderCommand still nags about items sitting >24h. Best-effort:
     * a mail failure never breaks the run. Assumes tenancy is initialized.
     */
    public function notifyPendingApprovals(Workspace $workspace): void
    {
        $count = AutoReplyQueueItem::query()->where('status', 'pending')->count();
        if ($count <= 0) {
            return;
        }

        // Throttle via a timestamp on the central workspace `data` JSON.
        $last = $workspace->getAttribute('approvals_notified_at');
        if (is_string($last) && Carbon::parse($last)->greaterThan(now()->subHour())) {
            return;
        }

        $workspace->setAttribute('approvals_notified_at', now()->toIso8601String());
        $workspace->save();

        try {
            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new ApprovalsPendingMail(
                    name: $name,
                    count: $count,
                    approvalsUrl: rtrim((string) config('app.url'), '/').'/approvals',
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Pending approvals email failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unreplied reviews without an active (pending/scheduled/published) queue
     * item, optionally limited to a review-date window. `failed` also blocks:
     * a failed publish (e.g. the review was deleted on Google) must park the
     * review for a human decision, not regenerate a fresh AI draft on every
     * pass and burn the allowance in a loop.
     */
    private function eligibleReviews(?CarbonInterface $from = null, ?CarbonInterface $until = null): Builder
    {
        return Review::query()
            ->whereNull('reply_text')
            ->when($from, fn ($q) => $q->where('created_at_external', '>=', $from))
            ->when($until, fn ($q) => $q->where('created_at_external', '<=', $until))
            ->whereDoesntHave('queueItems', fn ($q) => $q->whereIn('status', ['pending', 'published', 'scheduled', 'failed']))
            ->with('location');
    }

    /**
     * @param  array{generated:int, published:int, queued:int, skipped:int}  $stats
     */
    private function tally(?AutoReplyQueueItem $item, array &$stats): void
    {
        if ($item === null) {
            return;
        }

        if ($item->status === 'published') {
            $stats['generated']++;
            $stats['published']++;
        } elseif ($item->status === 'pending' || $item->status === 'scheduled') {
            $stats['generated']++;
            $stats['queued']++;
        } else {
            $stats['skipped']++;
        }
    }

    private function record(Review $review, string $text, string $status, string $mode, ?string $model = null, int $credits = 0, ?string $error = null, ?CarbonInterface $postAt = null, ?int $agentId = null): AutoReplyQueueItem
    {
        return AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => $text,
            'status' => $status,
            'mode' => $mode,
            'model' => $model,
            'ai_agent_id' => $agentId,
            'credits_spent' => $credits,
            'error' => $error,
            'post_at' => $postAt,
        ]);
    }

    /**
     * Post a reply to the provider and mark the review replied. Shared by the
     * instant-publish path and the deferred poster ([[AutoReplyPostDueCommand]]).
     */
    public function publish(Review $review, string $text, string $source): void
    {
        $provider = $this->providers->make();
        $accountId = $review->location?->zernio_account_id ?? 'fake-account';

        $provider->reply($accountId, $review->external_review_id, $text, $review->location?->external_id);

        $review->forceFill([
            'reply_text' => $text,
            'replied_at' => now(),
            'reply_status' => 'published',
            'reply_source' => $source,
        ])->save();
    }
}
