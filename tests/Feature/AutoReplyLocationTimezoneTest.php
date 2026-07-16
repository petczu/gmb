<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use Tests\TestCase;

/**
 * Auto-reply working hours are interpreted in the review's LOCATION timezone
 * (so a multi-city workspace schedules each reply in local time), falling back
 * to the workspace timezone and then UTC.
 */
class AutoReplyLocationTimezoneTest extends TestCase
{
    public function test_prefers_the_review_location_timezone(): void
    {
        $review = new Review;
        $review->setRelation('location', new Location(['timezone' => 'Asia/Dubai']));

        $tz = app(AutomationService::class)->timezoneFor(new Workspace, $review);

        $this->assertSame('Asia/Dubai', $tz);
    }

    public function test_falls_back_to_utc_without_a_location_timezone(): void
    {
        config(['app.timezone' => 'UTC']);

        $review = new Review;
        $review->setRelation('location', new Location(['timezone' => null]));

        $tz = app(AutomationService::class)->timezoneFor(new Workspace, $review);

        $this->assertSame('UTC', $tz);
    }
}
