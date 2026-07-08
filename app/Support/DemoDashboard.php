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
}
