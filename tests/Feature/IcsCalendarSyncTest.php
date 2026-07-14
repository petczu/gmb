<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalCalendar;
use App\Services\Posts\IcsCalendarSync;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ICS feed parsing (all-day, timed, multi-day, folded lines) and the sync
 * that replaces a calendar's materialized per-day events.
 */
class IcsCalendarSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('external_calendars', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('url', 2048);
            $table->string('color', 20)->default('green');
            $table->boolean('enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendar_events', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('external_calendar_id')->index();
            $table->date('date')->index();
            $table->string('title');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('external_calendar_events');
        Schema::dropIfExists('external_calendars');
        parent::tearDown();
    }

    private function sampleIcs(): string
    {
        $nextMonth = now()->addMonth();
        $allDay = $nextMonth->format('Ymd');
        $allDayEnd = $nextMonth->addDay()->format('Ymd');
        $multiStart = now()->addMonths(2)->format('Ymd');
        $multiEnd = now()->addMonths(2)->addDays(2)->format('Ymd');
        $timed = now()->addWeek()->format('Ymd');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'BEGIN:VEVENT',
            "DTSTART;VALUE=DATE:{$allDay}",
            "DTEND;VALUE=DATE:{$allDayEnd}",
            'SUMMARY:Staatsfeiertag',
            'END:VEVENT',
            'BEGIN:VEVENT',
            "DTSTART;VALUE=DATE:{$multiStart}",
            "DTEND;VALUE=DATE:{$multiEnd}",
            'SUMMARY:Team offsite with a very long',
            ' folded name\, indeed',
            'END:VEVENT',
            'BEGIN:VEVENT',
            "DTSTART:{$timed}T100000Z",
            "DTEND:{$timed}T113000Z",
            'SUMMARY:Booking: Escape room',
            'END:VEVENT',
            'BEGIN:VEVENT',
            'DTSTART;VALUE=DATE:20200101',
            'DTEND;VALUE=DATE:20200102',
            'SUMMARY:Way in the past',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }

    public function test_parse_expands_events_per_day_and_skips_out_of_window(): void
    {
        $rows = app(IcsCalendarSync::class)->parse($this->sampleIcs());

        $titles = array_column($rows, 'title');

        // Single all-day holiday: exclusive DTEND means exactly one row.
        $this->assertSame(1, count(array_keys($titles, 'Staatsfeiertag', true)));

        // Two-day event expands to two rows, with the folded line unescaped.
        $this->assertSame(2, count(array_keys($titles, 'Team offsite with a very longfolded name, indeed', true)));

        // Timed event yields exactly one row on its day.
        $this->assertSame(1, count(array_keys($titles, 'Booking: Escape room', true)));

        // Events outside the retention window are dropped.
        $this->assertNotContains('Way in the past', $titles);
    }

    public function test_sync_replaces_events_and_stamps_synced_at(): void
    {
        Http::fake(['calendar.test/*' => Http::response($this->sampleIcs())]);

        $calendar = ExternalCalendar::create(['name' => 'AT Holidays', 'url' => 'https://calendar.test/at.ics']);
        $calendar->events()->create(['date' => now()->toDateString(), 'title' => 'Stale event']);

        $this->assertTrue(app(IcsCalendarSync::class)->sync($calendar));

        $calendar->refresh();
        $this->assertNotNull($calendar->synced_at);
        $this->assertNull($calendar->sync_error);
        $this->assertSame(4, $calendar->events()->count());
        $this->assertSame(0, $calendar->events()->where('title', 'Stale event')->count());
    }

    public function test_failed_fetch_records_the_error_and_keeps_old_events(): void
    {
        Http::fake(['calendar.test/*' => Http::response('nope', 500)]);

        $calendar = ExternalCalendar::create(['name' => 'Broken', 'url' => 'https://calendar.test/broken.ics']);
        $calendar->events()->create(['date' => now()->toDateString(), 'title' => 'Keep me']);

        $this->assertFalse(app(IcsCalendarSync::class)->sync($calendar));

        $calendar->refresh();
        $this->assertStringContainsString('500', (string) $calendar->sync_error);
        $this->assertSame(1, $calendar->events()->count());
    }
}
