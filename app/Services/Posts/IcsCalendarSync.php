<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\ExternalCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fetches an external ICS feed and materializes its events as one row per
 * covered day (external_calendar_events), replacing the previous snapshot.
 * Covers the common public-feed shapes (all-day, timed, multi-day events);
 * recurrence rules are not expanded — holiday feeds list each instance.
 */
class IcsCalendarSync
{
    /** Days of history to keep; events further back are not stored. */
    private const PAST_DAYS = 90;

    /** How far ahead events are materialized. */
    private const FUTURE_DAYS = 550;

    /** Hard cap so a runaway feed cannot flood the tenant DB. */
    private const MAX_EVENTS = 3000;

    public function sync(ExternalCalendar $calendar): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'text/calendar, text/plain, */*'])
                ->get($calendar->url);

            if ($response->failed()) {
                throw new \RuntimeException("Feed returned HTTP {$response->status()}");
            }

            $days = $this->parse($response->body());
        } catch (Throwable $e) {
            $calendar->forceFill(['sync_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return false;
        }

        $calendar->events()->delete();

        foreach (array_chunk($days, 500) as $chunk) {
            $calendar->events()->createMany($chunk);
        }

        $calendar->forceFill(['synced_at' => now(), 'sync_error' => null])->save();

        return true;
    }

    /**
     * Parse ICS text into per-day event rows within the retention window.
     *
     * @return list<array{date: string, title: string}>
     */
    public function parse(string $ics): array
    {
        // Unfold continuation lines (RFC 5545: CRLF followed by a space/tab).
        $ics = preg_replace('/\r?\n[ \t]/', '', $ics) ?? '';

        $windowStart = CarbonImmutable::now()->subDays(self::PAST_DAYS)->startOfDay();
        $windowEnd = CarbonImmutable::now()->addDays(self::FUTURE_DAYS)->endOfDay();

        $rows = [];
        $event = null;

        foreach (preg_split('/\r?\n/', $ics) ?: [] as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $event = ['summary' => null, 'start' => null, 'end' => null, 'allDay' => false];

                continue;
            }

            if ($line === 'END:VEVENT' && $event !== null) {
                foreach ($this->expand($event, $windowStart, $windowEnd) as $row) {
                    $rows[] = $row;

                    if (count($rows) >= self::MAX_EVENTS) {
                        return $rows;
                    }
                }

                $event = null;

                continue;
            }

            if ($event === null || ! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $name = strtoupper(strtok($key, ';') ?: '');

            if ($name === 'SUMMARY') {
                $event['summary'] = $this->unescape($value);
            } elseif ($name === 'DTSTART' || $name === 'DTEND') {
                $date = $this->parseDate($value);

                if ($date !== null) {
                    $event[$name === 'DTSTART' ? 'start' : 'end'] = $date;
                    $event['allDay'] = $event['allDay'] || strlen(trim($value)) === 8;
                }
            }
        }

        return $rows;
    }

    /**
     * One row per covered day. For all-day events DTEND is exclusive per the
     * RFC, so a single-day holiday (DTEND = DTSTART + 1) yields one row.
     *
     * @param  array{summary: ?string, start: ?CarbonImmutable, end: ?CarbonImmutable, allDay: bool}  $event
     * @return list<array{date: string, title: string}>
     */
    private function expand(array $event, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        if ($event['start'] === null || blank($event['summary'])) {
            return [];
        }

        $first = $event['start']->startOfDay();
        $last = ($event['end'] ?? $event['start'])->startOfDay();

        if ($event['allDay'] && $event['end'] !== null) {
            $last = $last->subDay();
        }

        $last = $last->lessThan($first) ? $first : $last;

        $rows = [];
        for ($day = $first; $day->lessThanOrEqualTo($last); $day = $day->addDay()) {
            if ($day->between($windowStart, $windowEnd)) {
                $rows[] = ['date' => $day->format('Y-m-d'), 'title' => mb_substr((string) $event['summary'], 0, 255)];
            }
        }

        return $rows;
    }

    /** Accepts 20260714, 20260714T093000 and 20260714T093000Z. */
    private function parseDate(string $value): ?CarbonImmutable
    {
        $value = trim($value);

        try {
            if (preg_match('/^\d{8}$/', $value)) {
                return CarbonImmutable::createFromFormat('Ymd', $value)->startOfDay();
            }

            if (preg_match('/^\d{8}T\d{6}Z?$/', $value)) {
                return CarbonImmutable::createFromFormat('Ymd\THis', rtrim($value, 'Z'));
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function unescape(string $value): string
    {
        return trim(str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $value));
    }
}
