<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ReplyFailedMail;
use App\Models\AutoReplyQueueItem;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use App\Services\Ai\ReplyScheduler;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Support\ReplyFailure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Posts auto-replies whose scheduled `post_at` time has arrived. Replies are
 * generated up-front by [[AutomationService]] and parked as `scheduled` queue
 * items; this command publishes them at their "organic" time.
 */
class AutoReplyPostDueCommand extends Command
{
    protected $signature = 'auto-reply:post-due {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Post auto-replies whose scheduled time has arrived';

    public function handle(AutomationService $service, ReplyScheduler $scheduler): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $posted = $this->postDueForWorkspace($workspace, $service, $scheduler);
                $this->info(sprintf('[%s] posted %d', $workspace->slug, $posted));
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        return self::SUCCESS;
    }

    private function postDueForWorkspace(Workspace $workspace, AutomationService $service, ReplyScheduler $scheduler): int
    {
        $tz = $this->workspaceTimezone($workspace);
        $posted = 0;

        AutoReplyQueueItem::query()
            ->due()
            ->with('review.location')
            ->get()
            ->each(function (AutoReplyQueueItem $item) use ($service, $scheduler, $tz, $workspace, &$posted): void {
                $review = $item->review;

                if ($review === null || $review->reply_text !== null) {
                    $item->forceFill(['status' => 'skipped', 'decided_at' => now()])->save();

                    return;
                }

                // Re-check working hours: a window may have closed since scheduling
                // (e.g. the poster ran late). If still constrained and outside,
                // push to the next window instead of posting now. Items with a
                // decided_by were explicitly approved by a human (bulk approve
                // queues them here), so they publish as soon as possible instead.
                $automation = $item->decided_by === null ? $service->matching($review) : null;
                if ($automation !== null && $automation->respect_working_hours && is_array($automation->working_hours)) {
                    if (! $scheduler->isWithinWorkingHours(now(), $automation->working_hours, $tz)) {
                        // Normalize the location-tz instant to the app timezone so
                        // Eloquent stores it as UTC (not the shifted wall-clock).
                        $next = $scheduler->nextWindowStart(now(), $automation->working_hours, $tz)
                            ->setTimezone(date_default_timezone_get());
                        $item->forceFill(['post_at' => $next])->save();

                        return;
                    }
                }

                try {
                    $service->publish($review, $item->generated_text, $item->model !== null ? 'ai_auto' : 'manual');
                    $item->forceFill(['status' => 'published', 'decided_at' => now()])->save();
                    $posted++;
                } catch (Throwable $e) {
                    $item->forceFill(['status' => 'failed', 'error' => ReplyFailure::humanize($e)])->save();
                    Log::error('Auto-reply post-due failed', [
                        'workspace' => tenant('id'),
                        'queue_item' => $item->id,
                        'error' => $e->getMessage(),
                    ]);

                    $this->notifyReplyFailed($workspace, $review, $e->getMessage());
                }
            });

        return $posted;
    }

    /**
     * Best-effort: email the workspace owner that a scheduled reply failed to post.
     * Only wired to the deferred post-due failure path. Never throws.
     */
    private function notifyReplyFailed(Workspace $workspace, ?Review $review, string $error = ''): void
    {
        try {
            $businessName = $review?->location?->name ?? $workspace->name;
            $authorName = (string) ($review?->author_name ?? 'A customer');
            $snippet = Str::limit((string) ($review?->text ?? ''), 160);
            // Deep-link straight to the failed review (its reply slide-over, on
            // the Reviews page Failed tab), not the Approvals page — a failed
            // reply is no longer a pending approval.
            $base = rtrim((string) config('app.url'), '/').'/reviews';
            $reviewsUrl = $review !== null ? $base.'?review='.$review->id : $base;

            // Categorise the failure so the email can say whether we'll retry
            // (transient) or explain a structural cause (review gone / auth).
            $reason = ReplyFailure::reason($error);

            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new ReplyFailedMail(
                    name: $name,
                    businessName: $businessName,
                    authorName: $authorName,
                    snippet: $snippet,
                    reviewsUrl: $reviewsUrl,
                    lang: $lang,
                    reason: $reason,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Reply failed email failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Workspace timezone (stored in the tenant `data` JSON), UTC fallback. */
    private function workspaceTimezone(Workspace $workspace): string
    {
        $tz = $workspace->timezone ?? null;

        return is_string($tz) && $tz !== '' ? $tz : (string) config('app.timezone', 'UTC');
    }
}
