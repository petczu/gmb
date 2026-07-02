<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Bus\Queueable;
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
 * ReviewSync initializes the tenant itself from the given workspace, so this
 * job is safe to run from the central queue context.
 */
class SyncWorkspaceReviews implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(public string $workspaceId) {}

    public function handle(ReviewSync $sync): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $stats = $sync->syncWorkspace($workspace);

        Log::info('SyncWorkspaceReviews done', ['workspace' => $this->workspaceId] + $stats);
    }
}
