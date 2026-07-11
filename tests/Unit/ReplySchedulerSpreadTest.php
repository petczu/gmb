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
}
