<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Location;

/**
 * Sample numbers for the dashboard while a workspace has no location yet: the
 * widgets render this demo data (so the page looks alive instead of zeros) and
 * a small overlay invites the user to connect their Google Business Profile.
 * Deterministic values — no randomness, no queries beyond the location check.
 */
class DemoDashboard
{
    public static function active(): bool
    {
        return tenancy()->initialized && ! Location::query()->exists();
    }

    /** 5★..1★ counts for the star-distribution bar chart. */
    public static function starCounts(): array
    {
        return [24, 8, 3, 2, 1];
    }

    /**
     * Rating & volume trend series (last 30 days, 1-day buckets).
     *
     * @return array{ratings: list<float>, volumes: list<int>, labels: list<string>}
     */
    public static function trend(): array
    {
        $ratings = [4.4, 4.5, 4.3, 4.6, 4.7, 4.5, 4.6, 4.8, 4.6, 4.7, 4.5, 4.6, 4.7, 4.8, 4.6, 4.7, 4.9, 4.7, 4.6, 4.8, 4.7, 4.8, 4.6, 4.7, 4.8, 4.9, 4.7, 4.8, 4.7, 4.8];
        $volumes = [1, 0, 2, 1, 3, 1, 0, 2, 1, 1, 2, 0, 1, 3, 1, 2, 1, 0, 2, 1, 3, 1, 2, 1, 0, 2, 3, 1, 2, 2];

        $labels = [];
        $start = now()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            $labels[] = $start->copy()->addDays($i)->format('M j');
        }

        return ['ratings' => $ratings, 'volumes' => $volumes, 'labels' => $labels];
    }

    /**
     * GBP performance demo: totals + a 30-day daily views series.
     *
     * @return array{totals: array<string, int>, views: int, labels: list<string>, series: list<int>}
     */
    public static function performance(): array
    {
        $series = [28, 34, 41, 52, 61, 55, 47, 39, 44, 50, 58, 66, 71, 63, 54, 46, 42, 49, 57, 64, 60, 52, 45, 51, 59, 68, 74, 66, 58, 61];

        $labels = [];
        $start = now()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            $labels[] = $start->copy()->addDays($i)->format('M j');
        }

        return [
            'totals' => [
                'search_desktop' => 92,
                'search_mobile' => 355,
                'maps_desktop' => 58,
                'maps_mobile' => 740,
                'calls' => 84,
                'directions' => 312,
                'website_clicks' => 508,
                'bookings' => 0,
            ],
            'views' => 1245,
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /** @return list<array{keyword: string, impressions: int}> */
    public static function searchKeywords(): array
    {
        return [
            ['keyword' => 'escape room', 'impressions' => 637],
            ['keyword' => 'escape room near me', 'impressions' => 454],
            ['keyword' => 'escape game', 'impressions' => 287],
            ['keyword' => 'team building activities', 'impressions' => 130],
            ['keyword' => 'things to do with friends', 'impressions' => 96],
            ['keyword' => 'birthday party ideas', 'impressions' => 59],
            ['keyword' => 'puzzle room downtown', 'impressions' => 41],
        ];
    }
}
