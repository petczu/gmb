<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendReportEmail;
use App\Models\ReportSchedule;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendScheduledReportsCommand extends Command
{
    protected $signature = 'reports:send-scheduled {--force : Queue every enabled schedule regardless of its due date}';

    protected $description = 'Queue email delivery for report schedules that are due today';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $queued = 0;

        foreach (Workspace::query()->cursor() as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                foreach (ReportSchedule::query()->where('enabled', true)->get() as $schedule) {
                    if (! $this->option('force') && ! $schedule->isDue($now)) {
                        continue;
                    }

                    SendReportEmail::dispatch((string) $workspace->id, $schedule->id);
                    $queued++;
                    $this->line("queued: [{$workspace->slug}] {$schedule->name}");
                }
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        $this->info("Queued {$queued} report(s).");

        return self::SUCCESS;
    }
}
