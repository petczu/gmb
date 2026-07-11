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

        $windowMinutes = $this->toMinutes($wh['end']) - $this->toMinutes($wh['start']);
        if ($windowMinutes <= 1) {
            return $local;
        }

        return $local->addMinutes(mt_rand(0, $windowMinutes - 1))->addSeconds(mt_rand(0, 59));
    }

    /**
     * @param  array{days:array<int,int>,start:string,end:string}  $wh
     */
    public function isWithinWorkingHours(CarbonInterface $t, array $wh, string $tz): bool
    {
        $local = Carbon::instance($t->toDateTime())->setTimezone($tz);

        if (! in_array($local->dayOfWeekIso, $wh['days'], true)) {
            return false;
        }

        $minutes = $local->hour * 60 + $local->minute;

        return $minutes >= $this->toMinutes($wh['start']) && $minutes < $this->toMinutes($wh['end']);
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
        $startMinutes = $this->toMinutes($wh['start']);
        $endMinutes = $this->toMinutes($wh['end']);

        for ($i = 0; $i < 8; $i++) {
            $day = $local->copy()->addDays($i)->startOfDay();

            if (! in_array($day->dayOfWeekIso, $wh['days'], true)) {
                continue;
            }

            $windowStart = $day->copy()->addMinutes($startMinutes);

            // Same day: only usable if we are still before the window closes.
            if ($i === 0) {
                $nowMinutes = $local->hour * 60 + $local->minute;
                if ($nowMinutes >= $endMinutes) {
                    continue;
                }
                if ($nowMinutes >= $startMinutes) {
                    // Already inside the window — keep the current time.
                    return $local;
                }
            }

            return $windowStart;
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
