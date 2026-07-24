<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ReviewWidget;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatched after a location is connected (the picker). Dispatcher only: it
 * fans out one staggered {@see SyncLocationReviewsJob} per location so the
 * user isn't blocked and Zernio isn't hammered by a single long job.
 *
 * Unique per workspace: connecting several locations in a row dispatches many
 * of these; the lock collapses them into one fan-out.
 */
class SyncWorkspaceReviews implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    /** One pending/running fan-out per workspace at a time. */
    public int $uniqueFor = 300;

    public function __construct(public string $workspaceId) {}

    public function uniqueId(): string
    {
        return $this->workspaceId;
    }

    public function handle(): void
    {
        $count = SyncLocationReviewsJob::fanOutForWorkspace($this->workspaceId);

        // Rebuild embedded review-widget snapshots once the per-location syncs
        // have had time to finish, so the public embed picks up fresh reviews.
        $after = ($count * SyncLocationReviewsJob::STAGGER_SECONDS) + 120;

        ReviewWidget::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('active', true)
            ->pluck('id')
            ->each(fn (int $id) => BuildReviewWidgetSnapshotJob::dispatch($id)->delay(now()->addSeconds($after)));
    }
}
