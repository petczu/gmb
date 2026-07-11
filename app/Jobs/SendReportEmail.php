<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ScheduledReportMail;
use App\Models\ReportSchedule;
use App\Models\Workspace;
use App\Services\Reports\ReportPdf;
use App\Support\DashboardPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Generates a report PDF and emails it for one tenant schedule. Heavy
 * (Browsershot), so it runs on the queue. Initializes the tenant from the
 * workspace, then restores the previous context.
 */
class SendReportEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public string $workspaceId, public int $scheduleId) {}

    public function handle(ReportPdf $pdf): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        $previousLocale = app()->getLocale();
        tenancy()->initialize($workspace);

        try {
            $schedule = ReportSchedule::find($this->scheduleId);

            if ($schedule === null || ! $schedule->enabled) {
                return;
            }

            $recipients = $schedule->resolveRecipients($workspace);

            if ($recipients === []) {
                Log::warning('SendReportEmail: no recipients', ['schedule' => $schedule->id, 'workspace' => $this->workspaceId]);

                return;
            }

            $period = DashboardPeriod::fromFilters([
                'period' => $schedule->period,
                'location_id' => $schedule->location_ids ?: $schedule->location_id,
                'compare' => $schedule->compare,
            ]);

            // One PDF for all recipients → language is the SCHEDULE's, not each
            // recipient's. Set the app locale so the report blade labels AND the
            // email itself render in that language, then restore.
            $language = in_array($schedule->language, ['en', 'de'], true) ? $schedule->language : 'en';
            app()->setLocale($language);

            $result = $pdf->generate($period, $language, $schedule->blocks);

            Mail::to($recipients)->send(new ScheduledReportMail(
                businessName: $result['businessName'],
                periodLabel: $period->label(),
                summary: $result['summary'],
                pdfPath: $result['path'],
                pdfName: $result['filename'],
            ));

            @unlink($result['path']);

            $schedule->forceFill(['last_sent_at' => now()])->save();

            Log::info('SendReportEmail sent', ['schedule' => $schedule->id, 'recipients' => count($recipients)]);
        } finally {
            app()->setLocale($previousLocale);

            if ($previous !== null) {
                tenancy()->initialize($previous);
            } else {
                tenancy()->end();
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendReportEmail failed', [
            'workspace' => $this->workspaceId,
            'schedule' => $this->scheduleId,
            'error' => $exception->getMessage(),
        ]);
    }
}
