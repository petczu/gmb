<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Competitors\CompetitorTrends;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Battle-level aggregation: weighted-by-reviews rating, and the summed
 * reviews delta / sparkline across a group of competitor places (central
 * place_snapshots pointed at an in-memory sqlite).
 */
class CompetitorBattleTrendsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-10 12:00:00'));

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
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
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::connection('mysql')->dropIfExists('place_snapshots');
        parent::tearDown();
    }

    private function snapshot(string $placeId, string $day, ?float $rating, int $reviews): void
    {
        DB::connection('mysql')->table('place_snapshots')->insert([
            'place_id' => $placeId,
            'day' => $day,
            'rating' => $rating,
            'reviews_count' => $reviews,
        ]);
    }

    public function test_weighted_rating_favours_higher_review_volume(): void
    {
        // 5.0 with 1000 reviews and 3.0 with 10 reviews → close to 5.0.
        $rating = CompetitorTrends::weightedRating([
            ['rating' => 5.0, 'reviews_count' => 1000],
            ['rating' => 3.0, 'reviews_count' => 10],
        ]);

        $this->assertSame(4.98, $rating);

        // Simple average would be 4.0 — confirm weighting changed it.
        $this->assertNotSame(4.0, $rating);

        // No rated volume → null.
        $this->assertNull(CompetitorTrends::weightedRating([['rating' => null, 'reviews_count' => 0]]));
    }

    public function test_places_summary_sums_reviews_delta_and_spark(): void
    {
        // Place A: 100 → 130 over the window (+30). Place B: 200 → 210 (+10).
        $this->snapshot('A', '2026-06-25', 4.5, 100);
        $this->snapshot('A', '2026-07-05', 4.6, 130);
        $this->snapshot('B', '2026-06-25', 4.0, 200);
        $this->snapshot('B', '2026-07-05', 4.2, 210);

        $summary = app(CompetitorTrends::class)->placesSummary(
            ['A', 'B'],
            CarbonImmutable::parse('2026-06-24'),
            CarbonImmutable::parse('2026-07-10'),
        );

        $this->assertSame(40, $summary['reviews_delta']); // 30 + 10
        // Weighted rating delta: (0.1×130 + 0.2×210) / (130+210) = 55/340 ≈ 0.16.
        $this->assertEqualsWithDelta(0.16, $summary['rating_delta'], 0.01);
        // Spark: two in-window days, each the summed review count.
        $this->assertSame([300, 340], $summary['spark']);
    }

    public function test_empty_place_list_returns_nulls(): void
    {
        $summary = app(CompetitorTrends::class)->placesSummary([], CarbonImmutable::parse('2026-06-24'));

        $this->assertNull($summary['reviews_delta']);
        $this->assertSame([], $summary['spark']);
    }
}
