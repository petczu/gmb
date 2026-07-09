<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\PlaceSnapshot;
use App\Services\Competitors\CompetitorTrends;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Competitor trend math over daily snapshots: deltas against the window
 * baseline, sparkline series, own-location review growth and the snapshot
 * upsert (one row per competitor per day).
 */
class CompetitorTrendsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-05 12:00:00'));

        Schema::create('competitors', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('location_id');
            $table->string('place_id');
            $table->string('name');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });

        // Snapshots live on the CENTRAL connection (shared across tenants) —
        // point 'mysql' at an in-memory sqlite for the pinned model.
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('place_snapshots', function ($table): void {
            $table->increments('id');
            $table->string('place_id');
            $table->date('day');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unique(['place_id', 'day']);
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->unsignedTinyInteger('rating');
            $table->dateTime('created_at_external');
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('reviews');
        Schema::connection('mysql')->dropIfExists('place_snapshots');
        Schema::dropIfExists('competitors');
        parent::tearDown();
    }

    private function competitor(): Competitor
    {
        return Competitor::create([
            'location_id' => 1,
            'place_id' => 'place-1',
            'name' => 'Rival',
            'rating' => 4.60,
            'reviews_count' => 1290,
        ]);
    }

    public function test_summary_computes_deltas_from_window_baseline(): void
    {
        $competitor = $this->competitor();

        foreach ([
            ['2026-06-01', 4.40, 1200], // before the 4-week window → baseline
            ['2026-06-14', 4.50, 1240],
            ['2026-06-28', 4.55, 1265],
            ['2026-07-05', 4.60, 1290],
        ] as [$day, $rating, $count]) {
            PlaceSnapshot::create([
                'place_id' => $competitor->place_id, 'day' => $day,
                'rating' => $rating, 'reviews_count' => $count,
            ]);
        }

        $summary = app(CompetitorTrends::class)->summary($competitor, CarbonImmutable::today()->subWeeks(4));

        $this->assertSame(90, $summary['reviews_delta']); // 1290 - 1200
        $this->assertEqualsWithDelta(0.20, $summary['rating_delta'], 0.001);
        $this->assertSame([1240, 1265, 1290], $summary['spark']); // in-window points only
    }

    public function test_summary_needs_at_least_two_snapshots(): void
    {
        $competitor = $this->competitor();
        PlaceSnapshot::create([
            'place_id' => $competitor->place_id, 'day' => '2026-07-05',
            'rating' => 4.60, 'reviews_count' => 1290,
        ]);

        $summary = app(CompetitorTrends::class)->summary($competitor, CarbonImmutable::today()->subWeeks(4));

        $this->assertNull($summary['reviews_delta']);
        $this->assertNull($summary['rating_delta']);
    }

    public function test_record_upserts_one_row_per_day(): void
    {
        $competitor = $this->competitor();
        $trends = app(CompetitorTrends::class);

        $trends->record($competitor);
        $competitor->forceFill(['reviews_count' => 1300])->save();
        $trends->record($competitor); // same day → update, not duplicate

        $this->assertSame(1, PlaceSnapshot::count());
        $this->assertSame(1300, PlaceSnapshot::sole()->reviews_count);
    }

    public function test_own_new_reviews_counts_the_window_exactly(): void
    {
        DB::table('reviews')->insert([
            ['location_id' => 1, 'rating' => 5, 'created_at_external' => '2026-07-01 10:00:00'],
            ['location_id' => 1, 'rating' => 4, 'created_at_external' => '2026-06-20 10:00:00'],
            ['location_id' => 1, 'rating' => 5, 'created_at_external' => '2026-05-01 10:00:00'], // outside
            ['location_id' => 2, 'rating' => 5, 'created_at_external' => '2026-07-01 10:00:00'], // other location
        ]);

        $count = app(CompetitorTrends::class)->ownNewReviews(1, CarbonImmutable::today()->subWeeks(4));

        $this->assertSame(2, $count);
    }
}
