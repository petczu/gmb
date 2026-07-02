<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Account\WorkspaceDeletionService;
use Illuminate\Console\Command;

class PurgeDeletedWorkspacesCommand extends Command
{
    protected $signature = 'workspaces:purge-deleted
        {--force : Purge every pending workspace now, ignoring the grace window}';

    protected $description = 'Irreversibly purge workspaces whose deletion grace window has elapsed';

    public function handle(WorkspaceDeletionService $service): int
    {
        $graceDays = (int) config('services.account.deletion_grace_days', 30);
        $cutoff = now()->subDays($graceDays);

        $query = Workspace::query()->whereNotNull('deletion_requested_at');
        if (! $this->option('force')) {
            $query->where('deletion_requested_at', '<=', $cutoff);
        }

        $purged = 0;
        foreach ($query->get() as $workspace) {
            $this->line("purging: [{$workspace->slug}] {$workspace->name}");
            $service->purge($workspace);
            $purged++;
        }

        $this->info("Purged {$purged} workspace(s).");

        return self::SUCCESS;
    }
}
