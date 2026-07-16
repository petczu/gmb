<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Automation;
use App\Services\Ai\ReplyScheduler;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Replies deferred into the next working window must not all land on the
 * window's first second — a backlog would otherwise publish as one burst at
 * opening time.
 */
class ReplySchedulerSpreadTest extends TestCase
{
    private function automation(): Automation
    {
        $automation = new Automation;
        $automation->reply_delay_min_minutes = 0;
        $automation->reply_delay_max_minutes = 0;
        $automation->respect_working_hours = true;
        $automation->working_hours = ['days' => [1, 2, 3, 4, 5, 6, 7], 'start' => '10:00', 'end' => '18:00'];

        return $automation;
    }

    public function test_deferred_replies_are_spread_across_the_window(): void
    {
        $scheduler = new ReplyScheduler;
        $automation = $this->automation();
        $from = Carbon::parse('2026-07-11 23:30:00', 'UTC');

        $times = [];
        for ($i = 0; $i < 30; $i++) {
            $at = $scheduler->scheduleFor($automation, $from, 'UTC');

            $this->assertSame('2026-07-12', $at->format('Y-m-d'));
            $this->assertGreaterThanOrEqual('10:00:00', $at->format('H:i:s'));
            $this->assertLessThan('18:00:00', $at->format('H:i:s'));

            $times[] = $at->format('H:i:s');
        }

        $this->assertGreaterThan(5, count(array_unique($times)), 'deferred replies all landed on the same time');
    }

    public function test_time_already_inside_window_keeps_its_delay(): void
    {
        $scheduler = new ReplyScheduler;
        $automation = $this->automation();
        $from = Carbon::parse('2026-07-11 12:00:00', 'UTC');

        $at = $scheduler->scheduleFor($automation, $from, 'UTC');

        $this->assertSame('2026-07-11 12:00:00', $at->format('Y-m-d H:i:s'));
    }

    public function test_before_window_same_day_is_spread_within_that_window(): void
    {
        $scheduler = new ReplyScheduler;
        $automation = $this->automation();
        $from = Carbon::parse('2026-07-11 06:00:00', 'UTC');

        $at = $scheduler->scheduleFor($automation, $from, 'UTC');

        $this->assertSame('2026-07-11', $at->format('Y-m-d'));
        $this->assertGreaterThanOrEqual('10:00:00', $at->format('H:i:s'));
        $this->assertLessThan('18:00:00', $at->format('H:i:s'));
    }

    /** Escape rooms open late — a window that crosses midnight (10:00–01:00). */
    private function overnightAutomation(): Automation
    {
        $automation = new Automation;
        $automation->reply_delay_min_minutes = 5;
        $automation->reply_delay_max_minutes = 35;
        $automation->respect_working_hours = true;
        $automation->working_hours = ['days' => [1, 2, 3, 4, 5, 6, 7], 'start' => '10:00', 'end' => '01:00'];

        return $automation;
    }

    public function test_evening_inside_an_overnight_window_keeps_the_short_delay(): void
    {
        $scheduler = new ReplyScheduler;
        // 17:08 is inside 10:00–01:00; the reply should post ~5–35 min later the
        // SAME day, not be deferred to tomorrow's opening.
        $from = Carbon::parse('2026-07-16 17:08:00', 'UTC');

        $at = $scheduler->scheduleFor($this->overnightAutomation(), $from, 'UTC');

        $this->assertSame('2026-07-16', $at->format('Y-m-d'));
        $this->assertGreaterThanOrEqual('17:13:00', $at->format('H:i:s'));
        $this->assertLessThanOrEqual('17:43:00', $at->format('H:i:s'));
    }

    public function test_post_midnight_tail_is_still_inside_the_overnight_window(): void
    {
        $scheduler = new ReplyScheduler;
        $from = Carbon::parse('2026-07-16 00:30:00', 'UTC'); // before the 01:00 close

        $at = $scheduler->scheduleFor($this->overnightAutomation(), $from, 'UTC');

        // Inside → keep ~delay, same night (rolls a few minutes, may cross 01:00
        // which is fine — it was already inside when scheduled).
        $this->assertSame('2026-07-16', $at->format('Y-m-d'));
    }

    public function test_outside_an_overnight_window_defers_and_spreads(): void
    {
        $scheduler = new ReplyScheduler;
        $from = Carbon::parse('2026-07-16 05:00:00', 'UTC'); // 05:00 is outside 10:00–01:00

        // The window runs 10:00 today .. 01:00 tomorrow, so replies spread across
        // it legitimately roll past midnight into the early hours of the next day.
        $times = [];
        for ($i = 0; $i < 30; $i++) {
            $at = $scheduler->scheduleFor($this->overnightAutomation(), $from, 'UTC');
            $this->assertGreaterThanOrEqual('2026-07-16 10:00:00', $at->format('Y-m-d H:i:s'));
            $this->assertLessThan('2026-07-17 01:00:00', $at->format('Y-m-d H:i:s'));
            $times[] = $at->format('Y-m-d H:i:s');
        }

        $this->assertGreaterThan(5, count(array_unique($times)), 'overnight-deferred replies all landed on the same time');
    }
}
