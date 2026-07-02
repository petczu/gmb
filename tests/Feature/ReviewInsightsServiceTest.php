<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Reviews\ReviewInsightsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit-style coverage for the review-growth analysis. The service queries the
 * tenant Location/Review models on the default connection, so we build minimal
 * tables on the in-memory sqlite test DB and drive "now" with setTestNow —
 * no stancl tenancy bootstrap required.
 */
class ReviewInsightsServiceTest extends TestCase
{
    private ReviewInsightsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('review_goal')->nullable();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->unsignedTinyInteger('rating');
            $table->dateTime('created_at_external');
        });

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-15 12:00:00'));

        $this->service = new ReviewInsightsService;
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');

        parent::tearDown();
    }

    private function location(string $name, ?int $goal = null): int
    {
        return (int) DB::table('locations')->insertGetId(['name' => $name, 'review_goal' => $goal]);
    }

    private function reviews(int $locationId, int $count, int $daysAgo, int $rating = 5): void
    {
        $date = CarbonImmutable::now()->subDays($daysAgo)->format('Y-m-d H:i:s');

        for ($i = 0; $i < $count; $i++) {
            DB::table('reviews')->insert([
                'location_id' => $locationId,
                'rating' => $rating,
                'created_at_external' => $date,
            ]);
        }
    }

    public function test_goal_progress_marks_a_location_ahead_of_pace(): void
    {
        // Day 15 of a 30-day month: pro-rated expectation for a goal of 30 is 15.
        $id = $this->location('Vienna', goal: 30);
        $this->reviews($id, 18, daysAgo: 2);

        $progress = $this->service->goalProgress();

        $this->assertTrue($this->service->hasAnyGoal());
        $this->assertCount(1, $progress['rows']);
        $this->assertSame(18, $progress['rows'][0]['actual']);
        $this->assertSame(15, $progress['rows'][0]['expected']);
        $this->assertSame('ahead', $progress['rows'][0]['status']);
    }

    public function test_goal_progress_marks_a_location_behind_pace(): void
    {
        $id = $this->location('Graz', goal: 30);
        $this->reviews($id, 5, daysAgo: 1);

        $progress = $this->service->goalProgress();

        $this->assertSame('behind', $progress['rows'][0]['status']);
    }

    public function test_locations_without_a_goal_are_excluded_from_progress(): void
    {
        $this->location('No goal', goal: null);

        $this->assertFalse($this->service->hasAnyGoal());
        $this->assertSame([], $this->service->goalProgress()['rows']);
    }

    public function test_detects_a_stalled_location(): void
    {
        // Active over the last 90 days (8 reviews) but silent for the last 40.
        $id = $this->location('Stalled');
        $this->reviews($id, 8, daysAgo: 40);

        $anomalies = $this->anomaliesByType();

        $this->assertArrayHasKey('stalled', $anomalies);
        $this->assertSame(40, $anomalies['stalled']['detail']['days']);
    }

    public function test_a_freshly_active_location_is_not_stalled(): void
    {
        $id = $this->location('Active');
        $this->reviews($id, 8, daysAgo: 40);
        $this->reviews($id, 1, daysAgo: 2);

        $this->assertArrayNotHasKey('stalled', $this->anomaliesByType());
    }

    public function test_detects_a_negative_streak(): void
    {
        $id = $this->location('Angry');
        $this->reviews($id, 3, daysAgo: 1, rating: 1);

        $anomalies = $this->anomaliesByType();

        $this->assertArrayHasKey('negative_streak', $anomalies);
        $this->assertSame(3, $anomalies['negative_streak']['detail']['count']);
    }

    public function test_detects_a_volume_spike(): void
    {
        // Baseline ~2.5/week over the 8 weeks before last week, then 8 in 7 days.
        $id = $this->location('Spiky');
        $this->reviews($id, 20, daysAgo: 30);
        $this->reviews($id, 8, daysAgo: 3);

        $this->assertArrayHasKey('spike', $this->anomaliesByType());
    }

    public function test_detects_a_rating_drop(): void
    {
        // Prior 30-day window solid 5★, recent 30-day window 2★ (each >= 5).
        $id = $this->location('Slipping');
        $this->reviews($id, 6, daysAgo: 45, rating: 5);
        $this->reviews($id, 6, daysAgo: 10, rating: 2);

        $anomalies = $this->anomaliesByType();

        $this->assertArrayHasKey('rating_drop', $anomalies);
        $this->assertGreaterThanOrEqual(0.3, $anomalies['rating_drop']['detail']['prior'] - $anomalies['rating_drop']['detail']['recent']);
    }

    public function test_recap_reports_last_month_against_goal_and_previous(): void
    {
        $id = $this->location('Vienna', goal: 30);
        // 28 reviews in May 2026, 22 in April 2026 (relative to 15 Jun).
        $this->reviews($id, 28, daysAgo: 30);
        $this->reviews($id, 22, daysAgo: 61);

        $recap = $this->service->recap(CarbonImmutable::now()->subMonth());

        $row = $recap['rows'][0];
        $this->assertSame(28, $row['actual']);
        $this->assertSame(22, $row['previous']);
        $this->assertSame(6, $row['delta']);
        $this->assertSame(93, $row['percent']);
    }

    /**
     * @return array<string, array{location: string, type: string, detail: array<string, int|float>}>
     */
    private function anomaliesByType(): array
    {
        $out = [];
        foreach ($this->service->anomalies() as $anomaly) {
            $out[$anomaly['type']] = $anomaly;
        }

        return $out;
    }
}
