<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ExternalCalendar;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Posts\HolidayCalendarSync;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Generates an AI holiday calendar off the request: with web-search date
 * verification a generation takes minutes, far beyond any sane HTTP timeout
 * (a synchronous run held a PHP worker hostage and ended in nginx 502s).
 * The requesting user gets a bell notification when the calendar is ready
 * (or failed), tagged with the workspace so the scoped bell shows it.
 */
class GenerateHolidayCalendarJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** One long attempt: the generator reports its own failures. */
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public string $workspaceId,
        public int $calendarId,
        public ?int $userId = null,
        public bool $extend = false,
    ) {}

    public function handle(): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $calendar = ExternalCalendar::find($this->calendarId);
            if ($calendar === null || ! HolidayCalendarSync::isAiCalendar($calendar)) {
                return;
            }

            $sync = app(HolidayCalendarSync::class);
            $ok = $this->extend ? $sync->extend($calendar) : $sync->sync($calendar);

            $this->notify(
                $workspace,
                $ok
                    ? __('pages/posts.calendar_ai_ready_title', ['name' => $calendar->name])
                    : __('pages/posts.calendar_ai_failed_title', ['name' => $calendar->name]),
                $ok
                    ? trans_choice('pages/posts.calendar_events_count', $calendar->events()->count(), ['count' => $calendar->events()->count()])
                    : (string) $calendar->sync_error,
                $ok,
            );
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    /** Bell entry for the user who asked for the calendar. Best-effort. */
    private function notify(Workspace $workspace, string $title, string $body, bool $success): void
    {
        $user = $this->userId === null ? null : User::find($this->userId);
        if ($user === null) {
            return;
        }

        try {
            $data = Notification::make()
                ->title($title)
                ->body(Str::limit($body, 120))
                ->icon($success ? 'heroicon-o-sparkles' : 'heroicon-o-exclamation-triangle')
                ->iconColor($success ? 'success' : 'danger')
                ->getDatabaseMessage();
            $data['workspace_id'] = (string) $workspace->id;

            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => FilamentDatabaseNotification::class,
                'data' => $data,
                'read_at' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Holiday calendar notification failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
