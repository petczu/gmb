<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use Illuminate\Console\Command;

class AutomationRunCommand extends Command
{
    protected $signature = 'automations:run {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Run review-reply automations for unanswered reviews (generate + auto-publish/queue)';

    public function handle(AutomationService $service): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $stats = $service->processWorkspace($workspace);
                $this->info(sprintf(
                    '[%s] generated %d — published %d, queued %d, skipped %d',
                    $workspace->slug,
                    $stats['generated'],
                    $stats['published'],
                    $stats['queued'],
                    $stats['skipped'],
                ));
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        return self::SUCCESS;
    }
}
