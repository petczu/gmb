<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AiBudgetAlertMail;
use App\Services\Ai\AiSpend;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Daily guard on the GLOBAL monthly AI budget (AI_MONTHLY_BUDGET_USD): emails
 * the super-admin allowlist once per month per threshold (80%, 100%). Alerts
 * only — nothing is hard-stopped (customer auto-replies must not silently die).
 */
class AiBudgetCheckCommand extends Command
{
    protected $signature = 'ai:budget-check';

    protected $description = 'Alert super-admins when system-wide AI spend crosses the monthly budget thresholds';

    /** Budget shares (percent) that trigger an alert, highest first. */
    private const THRESHOLDS = [100, 80];

    public function handle(AiSpend $spend): int
    {
        $budget = $spend->budget();

        if ($budget === null) {
            $this->info('AI_MONTHLY_BUDGET_USD is not set — skipping.');

            return self::SUCCESS;
        }

        $month = CarbonImmutable::now()->format('Y-m');
        $spent = $spend->monthSpendUsd();
        $percent = (int) floor($spent / $budget * 100);

        foreach (self::THRESHOLDS as $threshold) {
            if ($percent < $threshold) {
                continue;
            }

            // Once per month per threshold; TTL comfortably outlives the month.
            $key = 'ai-budget-alert:'.$month.':'.$threshold;

            if (Cache::add($key, now()->toIso8601String(), now()->addDays(45))) {
                $this->notifySuperAdmins($threshold, $spent, $budget, $month);
                $this->info(sprintf('Alerted at %d%% (spent $%.2f of $%.2f).', $threshold, $spent, $budget));
            }

            break; // only the highest crossed threshold per run
        }

        return self::SUCCESS;
    }

    private function notifySuperAdmins(int $threshold, float $spent, float $budget, string $month): void
    {
        $recipients = array_filter((array) config('superadmin.emails', []));

        if ($recipients === []) {
            $this->warn('No super-admin emails configured — alert not sent.');

            return;
        }

        Mail::to($recipients)->send(new AiBudgetAlertMail(
            percent: $threshold,
            spentUsd: $spent,
            budgetUsd: $budget,
            month: $month,
        ));
    }
}
