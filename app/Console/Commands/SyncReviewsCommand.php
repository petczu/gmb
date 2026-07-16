<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncWorkspaceReviewsJob;
use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncReviewsCommand extends Command
{
    protected $signature = 'reviews:sync
        {workspace? : Workspace id or slug; omit to sync all}
        {--sync : Sync inline instead of queuing a job per workspace}';

    protected $description = 'Sync locations and reviews from the provider into workspace tenant DBs';

    public function handle(ReviewSync $sync): int
    {
        $workspaces = $this->resolveWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->warn('No workspaces to sync.');

            return self::SUCCESS;
        }

        foreach ($workspaces as $workspace) {
            if (! $this->option('sync')) {
                SyncWorkspaceReviewsJob::dispatch((string) $workspace->getKey());
                $this->line("[{$workspace->slug}] queued");

                continue;
            }

            $stats = $sync->syncWorkspace($workspace);
            $this->info(sprintf(
                '[%s] synced %d locations, %d reviews',
                $workspace->slug,
                $stats['locations'],
                $stats['reviews'],
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function resolveWorkspaces(): Collection
    {
        $arg = $this->argument('workspace');

        if ($arg === null) {
            return Workspace::query()->get();
        }

        return Workspace::query()
            ->where('id', $arg)
            ->orWhere('slug', $arg)
            ->get();
    }
}
