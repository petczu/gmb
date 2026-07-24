<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Syncs one workspace's locations and reviews from the provider. Queued so a
 * large backfill (many locations, paginated provider calls) never blocks the
 * scheduler or the console. ReviewSync initializes/tears down tenancy itself.
 */
class SyncWorkspaceReviewsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * Syncing a whole workspace fetches reviews per location, and Zernio can be
     * slow to respond (up to the 30s per-request timeout) or rate-limit us into
     * backoff sleeps. The default 120s worker timeout killed multi-location
     * syncs mid-run, so give this job a generous ceiling of its own. Kept below
     * the redis `retry_after` (960s) so it is never re-reserved while running.
     */
    public int $timeout = 900;

    /** @var array<int, int> seconds */
    public array $backoff = [60, 300];

    public function __construct(public readonly string $workspaceId) {}

    public function handle(ReviewSync $sync): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $sync->syncWorkspace($workspace);
    }
}
