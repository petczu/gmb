<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Console\Command;
use Throwable;

/**
 * Extend the free trial for workspaces by N months (default 1). Handles both
 * trial kinds:
 *  - generic trial (no Stripe subscription yet): push workspace.trial_ends_at
 *    forward locally — Stripe isn't involved, there's nothing to extend there.
 *  - Stripe trial (a `location` subscription that is still trialing): call
 *    Stripe's extendTrial so the trial_end moves on Stripe too.
 * Paying workspaces (a live, non-trial subscription) are always skipped — you
 * can't put an active payer back on trial.
 */
class ExtendTrialCommand extends Command
{
    protected $signature = 'subscriptions:extend-trial
        {--months=1 : How many months to extend the trial by}
        {--workspace= : Limit to a single workspace id or slug}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Extend the free trial for workspaces by N months (local + Stripe)';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $dry = (bool) $this->option('dry-run');

        $query = Workspace::query();
        if ($this->option('workspace') !== null) {
            $query->where('id', $this->option('workspace'))->orWhere('slug', $this->option('workspace'));
        }

        $local = 0;
        $stripe = 0;
        $skipped = 0;

        foreach ($query->get() as $workspace) {
            $subscription = $workspace->subscription(LocationBilling::SUBSCRIPTION);

            // A Stripe subscription that is still on trial → extend it on Stripe.
            if ($subscription !== null && $subscription->onTrial()) {
                $base = $subscription->trial_ends_at?->isFuture() ? $subscription->trial_ends_at : now();
                $newEnd = $base->copy()->addMonths($months);

                $this->line($this->row('stripe', $workspace, $subscription->trial_ends_at?->format('Y-m-d'), $newEnd->format('Y-m-d'), $dry));

                if (! $dry) {
                    try {
                        $subscription->extendTrial($newEnd);
                    } catch (Throwable $e) {
                        $this->warn(sprintf('    Stripe extendTrial failed for %s: %s', $workspace->slug ?? $workspace->id, $e->getMessage()));

                        continue;
                    }
                }
                $stripe++;

                continue;
            }

            // Any other live subscription means they're a paying customer.
            if ($subscription !== null && $subscription->valid()) {
                $skipped++;

                continue;
            }

            // No Stripe subscription → the generic (pre-subscription) trial.
            $base = $workspace->trial_ends_at !== null && $workspace->trial_ends_at->isFuture()
                ? $workspace->trial_ends_at
                : now();
            $newEnd = $base->copy()->addMonths($months);

            $this->line($this->row('local', $workspace, $workspace->trial_ends_at?->format('Y-m-d'), $newEnd->format('Y-m-d'), $dry));

            if (! $dry) {
                $workspace->forceFill(['trial_ends_at' => $newEnd])->save();
            }
            $local++;
        }

        $this->info(sprintf(
            '%d local + %d Stripe trial(s) %s, %d skipped (paying).',
            $local,
            $stripe,
            $dry ? 'would be extended' : 'extended',
            $skipped,
        ));

        return self::SUCCESS;
    }

    private function row(string $kind, Workspace $workspace, ?string $from, string $to, bool $dry): string
    {
        return sprintf(
            '  · [%s] %s: %s → %s%s',
            $kind,
            $workspace->slug ?? $workspace->id,
            $from ?? 'none',
            $to,
            $dry ? ' [dry-run]' : '',
        );
    }
}
