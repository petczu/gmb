<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LocationGroup;
use App\Support\DashboardPeriod;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The dashboard/report location filter accepts "g:{id}" group tokens, which
 * DashboardPeriod expands to the group's member location ids.
 */
class DashboardPeriodGroupsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('location_groups', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->json('location_ids')->nullable();
            $table->timestamps();
        });

        tenancy()->initialized = true;
    }

    protected function tearDown(): void
    {
        tenancy()->initialized = false;
        Schema::dropIfExists('location_groups');
        parent::tearDown();
    }

    public function test_a_group_token_expands_to_its_member_locations(): void
    {
        $group = LocationGroup::create(['name' => 'North', 'location_ids' => [2, 3]]);

        $period = DashboardPeriod::fromFilters([
            'period' => 'last_7',
            'location_id' => ['g:'.$group->id, 5],
        ]);

        $ids = $period->locationIds;
        sort($ids);

        $this->assertSame([2, 3, 5], $ids);
    }

    public function test_plain_location_ids_pass_through_unchanged(): void
    {
        $period = DashboardPeriod::fromFilters([
            'period' => 'last_7',
            'location_id' => [7],
        ]);

        $this->assertSame([7], $period->locationIds);
        $this->assertSame(7, $period->locationId);
    }
}
