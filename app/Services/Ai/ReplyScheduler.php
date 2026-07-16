<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Automation;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Computes the "organic" post time for an auto-reply: a random delay window
 * after generation, optionally clamped into the automation's working hours so
 * replies never land outside business hours and look human-paced.
 *
 * Working-hours shape: {"days":[1,2,3,4,5],"start":"09:00","end":"18:00"}
 * where days are ISO weekdays (1=Mon .. 7=Sun) and start/end are HH:MM local.
 */
class ReplyScheduler
{
    /**
     * Schedule a reply: $from + random(min..max) minutes, pushed into the next
     * working-hours window when the automation requires it.
     */
    public function scheduleFor(Automation $automation, CarbonInterface $from, string $tz): CarbonInterface
    {
        $min = max(0, (int) $automation->reply_delay_min_minutes);
        $max = max($min, (int) $automation->reply_delay_max_minutes);

        $delay = $min === $max ? $min : mt_rand($min, $max);

        $at = Carbon::instance($from->toDateTime())->setTimezone($tz)->addMinutes($delay);

        $workingHours = $this->normalizeWorkingHours($automation->working_hours);

        if ($automation->respect_working_hours && $workingHours !== null && ! $this->isWithinWorkingHours($at, $workingHours, $tz)) {
            $at = $this->spreadIntoWindow($this->nextWindowStart($at, $workingHours, $tz), $workingHours);
        }

        return $at;
    }

    /**
     * Deferred replies all land on the window's first second otherwise — a
     * backlog would publish as one burst at opening time, which both hammers
     * the provider and looks robotic on Google. Spread each reply to a random
     * minute across the working window instead.
     *
     * @param  array{days:array<int,int>,start:string,end:string}  $wh
     */
    private function spreadIntoWindow(CarbonInterface $windowStart, array $wh): CarbonInterface
    {
        $local = Carbon::instance($windowStart->toDateTime());

        // Only spread from the window's start; a same-day time already inside
        // the window (nextWindowStart returns it unchanged) keeps its delay.
        if ($local->hour * 60 + $local->minute !== $this->toMinutes($wh['start'])) {
            return $local;
        }

        $windowMinutes = $this->windowLength($wh);
        if ($windowMinutes <= 1) {
            return $local;
        }

        return $local->addMinutes(mt_rand(0, $windowMinutes - 1))->addSeconds(mt_rand(0, 59));
    }

    /** Window length in minutes, handling windows that cross midnight (end <= start). */
    private function windowLength(array $wh): int
    {
        $start = $this->toMinutes($wh['start']);
        $end = $this->toMinutes($wh['end']);

        return $end > $start ? $end - $start : 1440 - $start + $end;
    }

    /**
     * @param  array{days:array<int,int>,start:string,end:string}  $wh
     */
    public function isWithinWorkingHours(CarbonInterface $t, array $wh, string $tz): bool
    {
        $local = Carbon::instance($t->toDateTime())->setTimezone($tz);
        $minutes = $local->hour * 60 + $local->minute;
        $start = $this->toMinutes($wh['start']);
        $end = $this->toMinutes($wh['end']);

        // Same-day window (e.g. 10:00–18:00).
        if ($start < $end) {
            return in_array($local->dayOfWeekIso, $wh['days'], true)
                && $minutes >= $start && $minutes < $end;
        }

        // Overnight window (end <= start, e.g. 10:00–01:00): the evening portion
        // [start, 24:00) belongs to today; the early-morning tail [0, end)
        // belongs to the PREVIOUS day's window.
        if ($minutes >= $start) {
            return in_array($local->dayOfWeekIso, $wh['days'], true);
        }

        if ($minutes < $end) {
            return in_array($local->copy()->subDay()->dayOfWeekIso, $wh['days'], true);
        }

        return false;
    }

    /**
     * The start of the next working window at or after $t. If $t is before the
     * window on a working day, returns that day's start; otherwise scans forward
     * up to a week for the next working day.
     *
     * @param  array{days:array<int,int>,start:string,end:string}  $wh
     */
    public function nextWindowStart(CarbonInterface $t, array $wh, string $tz): CarbonInterface
    {
        $local = Carbon::instance($t->toDateTime())->setTimezone($tz);

        // Already inside a window (including an overnight window's post-midnight
        // tail) — keep the current time.
        if ($this->isWithinWorkingHours($local, $wh, $tz)) {
            return $local;
        }

        $startMinutes = $this->toMinutes($wh['start']);

        // Otherwise the next window opens at the earliest working-day start at or
        // after now (scan forward up to a week).
        for ($i = 0; $i < 8; $i++) {
            $day = $local->copy()->addDays($i)->startOfDay();

            if (! in_array($day->dayOfWeekIso, $wh['days'], true)) {
                continue;
            }

            $windowStart = $day->copy()->addMinutes($startMinutes);
            if ($windowStart->gte($local)) {
                return $windowStart;
            }
        }

        // No working day configured within a week — fall back to the input.
        return $local;
    }

    /**
     * @return array{days:array<int,int>,start:string,end:string}|null
     */
    private function normalizeWorkingHours(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $days = array_values(array_filter(array_map('intval', $raw['days'] ?? [])));
        $start = (string) ($raw['start'] ?? '');
        $end = (string) ($raw['end'] ?? '');

        if ($days === [] || $start === '' || $end === '') {
            return null;
        }

        return ['days' => $days, 'start' => $start, 'end' => $end];
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return $h * 60 + $m;
    }
}
