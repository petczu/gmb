<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Services\Listings\ListingPerformance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * GBP performance aggregation: merging metric totals + daily series across
 * locations, the views sum, keyword merging, caching, and failure tolerance
 * (a broken account is skipped, not fatal).
 */
class ListingPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reviews.zernio_base_url', 'https://zernio.test/api/v1');
        config()->set('services.reviews.zernio_key', 'test-key');
        config()->set('cache.default', 'array');

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    private function performancePayload(int $scale): array
    {
        return [
            'success' => true,
            'metrics' => [
                'BUSINESS_IMPRESSIONS_MOBILE_MAPS' => [
                    'total' => 10 * $scale,
                    'values' => [
                        ['date' => '2026-07-01', 'value' => 4 * $scale],
                        ['date' => '2026-07-02', 'value' => 6 * $scale],
                    ],
                ],
                'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH' => [
                    'total' => 5 * $scale,
                    'values' => [['date' => '2026-07-01', 'value' => 5 * $scale]],
                ],
                'CALL_CLICKS' => [
                    'total' => 3 * $scale,
                    'values' => [['date' => '2026-07-02', 'value' => 3 * $scale]],
                ],
            ],
        ];
    }

    public function test_metrics_merge_across_locations_and_cache(): void
    {
        Location::create(['name' => 'A', 'zernio_account_id' => 'acc-a']);
        Location::create(['name' => 'B', 'zernio_account_id' => 'acc-b']);
        Location::create(['name' => 'No account']);

        Http::fake([
            'zernio.test/api/v1/analytics/googlebusiness/performance*accountId=acc-a*' => Http::response($this->performancePayload(1)),
            'zernio.test/api/v1/analytics/googlebusiness/performance*accountId=acc-b*' => Http::response($this->performancePayload(2)),
        ]);

        $service = app(ListingPerformance::class);
        $start = CarbonImmutable::parse('2026-07-01');
        $end = CarbonImmutable::parse('2026-07-02');

        $result = $service->metrics(null, $start, $end);

        $this->assertTrue($result['available']);
        // maps_mobile 10+20, search_desktop 5+10 → views 45; calls 3+6.
        $this->assertSame(45, $result['views']);
        $this->assertSame(30, $result['totals']['maps_mobile']);
        $this->assertSame(9, $result['totals']['calls']);
        $this->assertSame(18, $result['series']['2026-07-02']['maps_mobile']);
        $this->assertSame(9, $result['series']['2026-07-02']['calls']);

        // Chart-ready series is zero-filled per day.
        $daily = ListingPerformance::dailySeries($result['series'], $start, $end);
        $this->assertSame(['Jul 1', 'Jul 2'], $daily['labels']);
        $this->assertSame([27, 18], $daily['values']);

        // Second call served from cache: no extra HTTP requests.
        $service->metrics(null, $start, $end);
        Http::assertSentCount(2);
    }

    public function test_failed_account_is_skipped(): void
    {
        Location::create(['name' => 'A', 'zernio_account_id' => 'acc-a']);
        Location::create(['name' => 'B', 'zernio_account_id' => 'acc-broken']);

        Http::fake([
            'zernio.test/api/v1/analytics/googlebusiness/performance*accountId=acc-a*' => Http::response($this->performancePayload(1)),
            'zernio.test/api/v1/analytics/googlebusiness/performance*accountId=acc-broken*' => Http::response(['error' => 'nope'], 500),
        ]);

        $result = app(ListingPerformance::class)->metrics(
            null,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-02'),
        );

        $this->assertTrue($result['available']);
        $this->assertSame(15, $result['views']);
    }

    public function test_keywords_merge_and_sort(): void
    {
        Location::create(['name' => 'A', 'zernio_account_id' => 'acc-a']);
        Location::create(['name' => 'B', 'zernio_account_id' => 'acc-b']);

        Http::fake([
            'zernio.test/api/v1/analytics/googlebusiness/search-keywords*accountId=acc-a*' => Http::response([
                'keywords' => [
                    ['keyword' => 'escape room', 'impressions' => 100],
                    ['keyword' => 'escape game', 'impressions' => 40],
                ],
            ]),
            'zernio.test/api/v1/analytics/googlebusiness/search-keywords*accountId=acc-b*' => Http::response([
                'keywords' => [
                    ['keyword' => 'escape game', 'impressions' => 70],
                ],
            ]),
        ]);

        $keywords = app(ListingPerformance::class)->keywords(null);

        $this->assertSame([
            ['keyword' => 'escape game', 'impressions' => 110],
            ['keyword' => 'escape room', 'impressions' => 100],
        ], $keywords);
    }

    public function test_unconfigured_service_returns_empty(): void
    {
        config()->set('services.reviews.zernio_key', null);

        $result = app(ListingPerformance::class)->metrics(
            null,
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-02'),
        );

        $this->assertFalse($result['available']);
        $this->assertSame(0, $result['views']);
    }
}
