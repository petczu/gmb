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

        Schema::connection('mysql')->create('place_reviews', function ($table): void {
            $table->increments('id');
            $table->string('place_id');
            $table->string('review_id');
            $table->decimal('rating', 2, 1)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('author')->nullable();
            $table->text('text')->nullable();
            $table->string('language', 8)->nullable();
            $table->timestamps();
            $table->unique(['place_id', 'review_id']);
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::connection('mysql')->dropIfExists('place_reviews');
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

    private function review(string $placeId, string $reviewedAt, string $reviewId): void
    {
        DB::connection('mysql')->table('place_reviews')->insert([
            'place_id' => $placeId,
            'review_id' => $reviewId,
            'reviewed_at' => $reviewedAt,
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

    public function test_growth_series_rebases_or_keeps_absolute_by_mode(): void
    {
        $this->snapshot('p1', '2026-07-08', 4.5, 100);
        $this->snapshot('p1', '2026-07-09', 4.5, 105);
        $this->snapshot('p1', '2026-07-10', 4.5, 112);

        $start = CarbonImmutable::parse('2026-07-08');
        $end = CarbonImmutable::parse('2026-07-10');

        // Growth: rebased to 0 at the window start; the leading zero is hidden
        // (null) so the line starts at the first real change.
        $growth = app(CompetitorTrends::class)->growthSeries(['p1'], [], $start, $end, 'growth');
        $this->assertSame([null, 5, 12], $growth['places']['p1']);

        // Total: absolute review counts.
        $total = app(CompetitorTrends::class)->growthSeries(['p1'], [], $start, $end, 'total');
        $this->assertSame([100, 105, 112], $total['places']['p1']);
    }

    public function test_growth_series_uses_backfilled_reviews_for_full_history(): void
    {
        // Backfilled reviews (exact dates) cover days snapshots never saw.
        $this->review('p1', '2026-07-08 09:00:00', 'a');
        $this->review('p1', '2026-07-08 15:00:00', 'b');
        $this->review('p1', '2026-07-09 10:00:00', 'c');
        $this->review('p1', '2026-07-10 11:00:00', 'd');
        // Real current total is higher than what we captured (older reviews
        // beyond the 4490 cap): lift Total mode up to it.
        $this->snapshot('p1', '2026-07-10', 4.5, 1000);

        $start = CarbonImmutable::parse('2026-07-08');
        $end = CarbonImmutable::parse('2026-07-10');

        // Growth: cumulative NEW reviews since the window start (2 on day 1).
        $growth = app(CompetitorTrends::class)->growthSeries(['p1'], [], $start, $end, 'growth');
        $this->assertSame([2, 3, 4], $growth['places']['p1']);

        // Total: captured cumulative lifted to the real total (1000 = 4 captured
        // + 996 older) → 998, 999, 1000.
        $total = app(CompetitorTrends::class)->growthSeries(['p1'], [], $start, $end, 'total');
        $this->assertSame([998, 999, 1000], $total['places']['p1']);
    }
}
