<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Workspace;
use App\Services\Posts\ExternalPostImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pulls a workspace's previously-published Google posts back into the app
 * (via Zernio external posts). Queued because the on-demand platform sync plus
 * paginated history walk can take a while per account.
 */
class ImportExternalPostsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @var array<int, int> seconds */
    public array $backoff = [60, 300];

    public function __construct(public readonly string $workspaceId) {}

    public function handle(ExternalPostImporter $importer): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $importer->import();
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
