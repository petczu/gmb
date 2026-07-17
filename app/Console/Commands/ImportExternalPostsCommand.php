<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ImportExternalPostsJob;
use App\Models\Workspace;
use App\Services\Posts\ExternalPostImporter;
use Illuminate\Console\Command;

/**
 * Backfills previously-published Google posts (Zernio external posts) into every
 * workspace's calendar. Going forward, new native posts arrive on their own via
 * the post.external.created webhook — this command is the one-off catch-up for
 * history that predates the webhook.
 */
class ImportExternalPostsCommand extends Command
{
    protected $signature = 'posts:import-external
        {workspace? : Limit to a single workspace id or slug}
        {--sync : Import inline instead of queuing a job per workspace}';

    protected $description = 'Import previously-published Google posts into each workspace calendar';

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
            if (! $this->option('sync')) {
                ImportExternalPostsJob::dispatch((string) $workspace->getKey());
                $this->line("{$workspace->slug}: queued");

                continue;
            }

            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $result = app(ExternalPostImporter::class)->import();
                $this->line("{$workspace->slug}: {$result['imported']} new / {$result['seen']} seen from {$result['locations']} location(s)");
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }
}
