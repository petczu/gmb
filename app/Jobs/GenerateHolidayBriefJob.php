<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalCalendarEvent;
use App\Models\Workspace;
use App\Services\Posts\HolidayBriefWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Writes a holiday explainer off the request: the AI call takes seconds and
 * Livewire runs one request per component at a time, so an inline call froze
 * every calendar click until it finished. The popup polls the stored brief.
 */
class GenerateHolidayBriefJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public string $workspaceId,
        public int $eventId,
        public string $locale,
    ) {}

    public function handle(HolidayBriefWriter $writer): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $event = ExternalCalendarEvent::find($this->eventId);
            if ($event === null) {
                return;
            }

            try {
                $writer->ensure($event, $this->locale);
            } catch (Throwable $e) {
                report($e);
                // The polling popup turns this marker into a friendly error.
                Cache::put($writer->failureKey($event, $this->locale), true, now()->addMinutes(2));
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
