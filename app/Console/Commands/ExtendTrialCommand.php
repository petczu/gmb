<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Workspace;
use Illuminate\Console\Command;

/**
 * Extend the free trial for workspaces that aren't paying yet: push
 * trial_ends_at forward by N months (default 1) from whichever is later, the
 * current trial end or now, so everyone gets at least N fresh months. Paying
 * workspaces (an active subscription) are skipped unless --include-subscribed.
 */
class ExtendTrialCommand extends Command
{
    protected $signature = 'subscriptions:extend-trial
        {--months=1 : How many months to extend the trial by}
        {--workspace= : Limit to a single workspace id or slug}
        {--include-subscribed : Also extend workspaces that already have an active subscription}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Extend the free trial for workspaces by N months';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $dry = (bool) $this->option('dry-run');
        $includeSubscribed = (bool) $this->option('include-subscribed');

        $query = Workspace::query();
        if ($this->option('workspace') !== null) {
            $query->where('id', $this->option('workspace'))->orWhere('slug', $this->option('workspace'));
        }

        $extended = 0;
        $skipped = 0;

        foreach ($query->get() as $workspace) {
            if (! $includeSubscribed && $workspace->subscribed()) {
                $skipped++;

                continue;
            }

            $base = $workspace->trial_ends_at !== null && $workspace->trial_ends_at->isFuture()
                ? $workspace->trial_ends_at
                : now();
            $newEnd = $base->copy()->addMonths($months);

            $this->line(sprintf(
                '  · %s: %s → %s%s',
                $workspace->slug ?? $workspace->id,
                $workspace->trial_ends_at?->format('Y-m-d') ?? 'none',
                $newEnd->format('Y-m-d'),
                $dry ? ' [dry-run]' : '',
            ));

            if (! $dry) {
                $workspace->forceFill(['trial_ends_at' => $newEnd])->save();
            }
            $extended++;
        }

        $this->info(sprintf(
            '%d workspace(s) %s, %d skipped (already subscribed).',
            $extended,
            $dry ? 'would be extended' : 'extended',
            $skipped,
        ));

        return self::SUCCESS;
    }
}
