<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Console\Command;

class SyncReviewsCommand extends Command
{
    protected $signature = 'reviews:sync {workspace? : Workspace id or slug; omit to sync all}';

    protected $description = 'Sync locations and reviews from the provider into workspace tenant DBs';

    public function handle(ReviewSync $sync): int
    {
        $workspaces = $this->resolveWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->warn('No workspaces to sync.');

            return self::SUCCESS;
        }

        foreach ($workspaces as $workspace) {
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
     * @return \Illuminate\Support\Collection<int, Workspace>
     */
    private function resolveWorkspaces(): \Illuminate\Support\Collection
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
