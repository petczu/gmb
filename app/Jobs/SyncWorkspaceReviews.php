<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ReviewWidget;
use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes a workspace's reviews off the request cycle. Dispatched after a
 * location is connected (the picker) so the user isn't blocked while hundreds
 * of reviews are pulled from Zernio.
 *
 * Unique per workspace: connecting many locations in a row dispatches many
 * jobs, and each one syncs the WHOLE workspace — running them in parallel
 * multiplied the Zernio calls and tripped the 429 rate limit.
 *
 * ReviewSync initializes the tenant itself from the given workspace, so this
 * job is safe to run from the central queue context.
 */
class SyncWorkspaceReviews implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    /** One pending/running sync per workspace at a time. */
    public int $uniqueFor = 900;

    public function __construct(public string $workspaceId) {}

    public function uniqueId(): string
    {
        return $this->workspaceId;
    }

    public function handle(ReviewSync $sync): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $stats = $sync->syncWorkspace($workspace);

        Log::info('SyncWorkspaceReviews done', ['workspace' => $this->workspaceId] + $stats);

        // Keep embedded review widgets fresh: a sync can bring in new reviews
        // that should surface in the snapshot the public embed serves.
        ReviewWidget::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('active', true)
            ->pluck('id')
            ->each(fn (int $id) => BuildReviewWidgetSnapshotJob::dispatch($id));
    }
}
