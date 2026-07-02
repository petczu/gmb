<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Billing\AutoRechargeService;
use Illuminate\Console\Command;

/**
 * Tops up AI credits for workspaces that opted into auto-recharge and have
 * dropped to/below their threshold. All gating + the off-session charge live in
 * AutoRechargeService::recharge() (which self-guards via eligible()), so this
 * command just feeds it the candidate workspaces. Central only — no tenancy.
 */
class AiAutoRechargeCommand extends Command
{
    protected $signature = 'ai:auto-recharge {workspace? : Limit to a single workspace id}';

    protected $description = 'Auto top-up AI credits for opted-in workspaces below their threshold';

    public function handle(AutoRechargeService $service): int
    {
        // auto_recharge_enabled lives in the stancl `data` JSON column, not a real
        // column, so we can't filter it in SQL. Iterate all workspaces instead —
        // recharge()/eligible() short-circuits cheaply on the opt-in flag first.
        $query = Workspace::query();

        if ($id = $this->argument('workspace')) {
            $query->whereKey($id);
        }

        $toppedUp = 0;

        $query->cursor()->each(function (Workspace $workspace) use ($service, &$toppedUp): void {
            if ($service->recharge($workspace)) {
                $toppedUp++;
                $this->line("Topped up: {$workspace->id}");
            }
        });

        $this->info("Auto-recharge done. Topped up {$toppedUp} workspace(s).");

        return self::SUCCESS;
    }
}
