<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DashboardPeriod;
use PHPUnit\Framework\TestCase;

class DashboardPeriodTest extends TestCase
{
    public function test_no_location_filter_means_all_locations(): void
    {
        $period = DashboardPeriod::fromFilters(['period' => 'last_7']);

        $this->assertSame([], $period->locationIds);
        $this->assertNull($period->locationId);
    }

    public function test_scalar_location_id_from_report_pages_is_normalized(): void
    {
        $period = DashboardPeriod::fromFilters(['period' => 'last_7', 'location_id' => '5']);

        $this->assertSame([5], $period->locationIds);
        $this->assertSame(5, $period->locationId);
    }

    public function test_multi_select_array_keeps_all_ids(): void
    {
        $period = DashboardPeriod::fromFilters(['period' => 'last_7', 'location_id' => ['3', 7]]);

        $this->assertSame([3, 7], $period->locationIds);
        $this->assertNull($period->locationId);
    }

    public function test_empty_and_null_entries_are_dropped(): void
    {
        $period = DashboardPeriod::fromFilters(['period' => 'last_7', 'location_id' => ['', null, '4']]);

        $this->assertSame([4], $period->locationIds);
        $this->assertSame(4, $period->locationId);
    }

    public function test_single_array_selection_fills_location_id(): void
    {
        $period = DashboardPeriod::fromFilters(['period' => 'last_7', 'location_id' => [9]]);

        $this->assertSame([9], $period->locationIds);
        $this->assertSame(9, $period->locationId);
    }
}
