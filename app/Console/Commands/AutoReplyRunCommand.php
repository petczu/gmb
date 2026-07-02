<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Ai\AutoReplyService;
use Illuminate\Console\Command;

class AutoReplyRunCommand extends Command
{
    protected $signature = 'auto-reply:run {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Generate AI auto-replies for unreplied reviews per the configured per-star rules';

    public function handle(AutoReplyService $service): int
    {
        $workspaces = $this->resolveWorkspaces();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $stats = $service->processWorkspace($workspace);
                $this->info(sprintf(
                    '[%s] generated %d (published %d, queued %d, skipped %d)',
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

    /**
     * @return \Illuminate\Support\Collection<int, Workspace>
     */
    private function resolveWorkspaces(): \Illuminate\Support\Collection
    {
        $arg = $this->argument('workspace');

        if ($arg === null) {
            return Workspace::query()->get();
        }

        return Workspace::query()->where('id', $arg)->orWhere('slug', $arg)->get();
    }
}
