<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Posts\ExternalPostImporter;
use Illuminate\Console\Command;

/**
 * Per-location snapshot of external Google posts. Zernio's external sync only
 * exposes the account's currently selected location, so this walks each
 * location (select, sync, read, upsert) to cover them all. Runs inline (it
 * sleeps ~15s between locations of one account for Zernio's debounce), so
 * prefer a scheduled/queued context over an HTTP request.
 */
class SnapshotExternalPostsCommand extends Command
{
    protected $signature = 'posts:snapshot-external
        {workspace? : Limit to a single workspace id or slug}';

    protected $description = 'Snapshot every connected location\'s external Google posts (select + sync per location)';

    public function handle(): int
    {
        $workspaces = $this->argument('workspace') !== null
            ? Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get()
            : Workspace::query()->get();

        if ($workspaces->isEmpty()) {
            $this->warn('No matching workspaces.');

            return self::SUCCESS;
        }

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $result = app(ExternalPostImporter::class)->snapshot();
                $this->line("{$workspace->slug}: {$result['imported']} imported across {$result['locations']} location(s)");
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }
}
