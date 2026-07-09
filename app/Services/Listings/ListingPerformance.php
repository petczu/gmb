<?php

declare(strict_types=1);

namespace App\Services\Listings;

use App\Models\Location;
use App\Services\Zernio\ZernioRestClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google Business Profile performance (impressions, calls, directions,
 * website clicks, bookings) and search keywords, aggregated across the
 * workspace's locations via Zernio's analytics endpoints.
 *
 * Responses are cached per location + window: the data only updates daily on
 * Google's side (and lags a few days), while the analytics endpoints have the
 * strictest rate limits. Locations that fail to fetch are skipped silently so
 * one broken connection never blanks the whole dashboard.
 */
class ListingPerformance
{
    /** Zernio/Google metric name => our short key. */
    public const METRICS = [
        'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH' => 'search_desktop',
        'BUSINESS_IMPRESSIONS_MOBILE_SEARCH' => 'search_mobile',
        'BUSINESS_IMPRESSIONS_DESKTOP_MAPS' => 'maps_desktop',
        'BUSINESS_IMPRESSIONS_MOBILE_MAPS' => 'maps_mobile',
        'CALL_CLICKS' => 'calls',
        'BUSINESS_DIRECTION_REQUESTS' => 'directions',
        'WEBSITE_CLICKS' => 'website_clicks',
        'BUSINESS_BOOKINGS' => 'bookings',
    ];

    /** The four impression metrics that add up to "views". */
    public const VIEW_KEYS = ['search_desktop', 'search_mobile', 'maps_desktop', 'maps_mobile'];

    private const CACHE_TTL = 21600; // 6 hours

    public function __construct(private readonly ZernioRestClient $client) {}

    public function configured(): bool
    {
        return $this->client->configured();
    }

    /**
     * Aggregated metrics for the given window, summed across locations.
     *
     * totals: short key => sum; series: date (Y-m-d) => short key => value.
     *
     * @param  ?int  $locationId  restrict to one location (null = all)
     * @return array{totals: array<string, int>, views: int, series: array<string, array<string, int>>, available: bool}
     */
    public function metrics(?int $locationId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $totals = array_fill_keys(array_values(self::METRICS), 0);
        $series = [];
        $available = false;

        foreach ($this->accounts($locationId) as $accountId) {
            $payload = $this->fetchPerformance($accountId, $start->format('Y-m-d'), $end->format('Y-m-d'));

            if ($payload === []) {
                continue;
            }

            $available = true;

            foreach ((array) ($payload['metrics'] ?? []) as $name => $metric) {
                $key = self::METRICS[$name] ?? null;
                if ($key === null) {
                    continue;
                }

                $totals[$key] += (int) ($metric['total'] ?? 0);

                foreach ((array) ($metric['values'] ?? []) as $point) {
                    $date = (string) ($point['date'] ?? '');
                    if ($date === '') {
                        continue;
                    }
                    $series[$date][$key] = ($series[$date][$key] ?? 0) + (int) ($point['value'] ?? 0);
                }
            }
        }

        ksort($series);

        return [
            'totals' => $totals,
            'views' => array_sum(array_intersect_key($totals, array_flip(self::VIEW_KEYS))),
            'series' => $series,
            'available' => $available,
        ];
    }

    /**
     * Top search keywords (merged across locations, impressions summed).
     * Keyword impressions are monthly on Google's side; the API defaults to
     * the last 3 months.
     *
     * @return list<array{keyword: string, impressions: int}>
     */
    public function keywords(?int $locationId, ?string $startMonth = null, ?string $endMonth = null, int $limit = 10): array
    {
        $merged = [];

        foreach ($this->accounts($locationId) as $accountId) {
            foreach ($this->fetchKeywords($accountId, $startMonth, $endMonth) as $row) {
                $keyword = trim((string) ($row['keyword'] ?? ''));
                if ($keyword === '') {
                    continue;
                }
                $merged[$keyword] = ($merged[$keyword] ?? 0) + (int) ($row['impressions'] ?? 0);
            }
        }

        arsort($merged);

        return collect($merged)
            ->take($limit)
            ->map(fn (int $impressions, string $keyword): array => ['keyword' => $keyword, 'impressions' => $impressions])
            ->values()
            ->all();
    }

    /**
     * Daily "views" series (all four impression metrics summed), one value per
     * day between $start and $end, zero-filled — chart-ready.
     *
     * @param  array<string, array<string, int>>  $series  the metrics()['series'] map
     * @return array{labels: list<string>, values: list<int>}
     */
    public static function dailySeries(array $series, CarbonImmutable $start, CarbonImmutable $end, string $metric = 'views'): array
    {
        $labels = [];
        $values = [];

        for ($day = $start->startOfDay(); $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $point = $series[$key] ?? [];

            $labels[] = $day->format('M j');
            $values[] = $metric === 'views'
                ? array_sum(array_intersect_key($point, array_flip(self::VIEW_KEYS)))
                : (int) ($point[$metric] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Zernio account ids for the scope (locations without one are skipped).
     * Callers run in tenant context (dashboard widgets, reports).
     */
    private function accounts(?int $locationId): Collection
    {
        if (! $this->configured()) {
            return collect();
        }

        return Location::query()
            ->when($locationId, fn ($q, int $id) => $q->whereKey($id))
            ->whereNotNull('zernio_account_id')
            ->pluck('zernio_account_id')
            ->unique()
            ->values();
    }

    /**
     * Empty array = fetch failed (cached too, so a broken account is retried
     * at most once per TTL — Cache::remember would re-run on null).
     *
     * @return array<string, mixed>
     */
    private function fetchPerformance(string $accountId, string $from, string $to): array
    {
        return (array) Cache::remember(
            "listing-perf:{$accountId}:{$from}:{$to}",
            self::CACHE_TTL,
            function () use ($accountId, $from, $to): array {
                try {
                    return $this->client->performance($accountId, $from, $to);
                } catch (Throwable $e) {
                    Log::debug('GBP performance fetch failed', ['account' => $accountId, 'error' => $e->getMessage()]);

                    return [];
                }
            },
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchKeywords(string $accountId, ?string $startMonth, ?string $endMonth): array
    {
        return (array) Cache::remember(
            'listing-keywords:'.$accountId.':'.($startMonth ?? 'auto').':'.($endMonth ?? 'auto'),
            self::CACHE_TTL,
            function () use ($accountId, $startMonth, $endMonth): array {
                try {
                    return $this->client->searchKeywords($accountId, $startMonth, $endMonth);
                } catch (Throwable $e) {
                    Log::debug('GBP keywords fetch failed', ['account' => $accountId, 'error' => $e->getMessage()]);

                    return [];
                }
            },
        );
    }
}
