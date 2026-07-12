<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Competitor;
use App\Models\PlaceSnapshot;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Trends over the daily place snapshots (CENTRAL, keyed by place_id and
 * shared across workspaces): review/rating deltas for a period and a
 * sparkline series. The own location's growth comes straight from the
 * reviews table (exact, date-based).
 */
class CompetitorTrends
{
    /** Record today's snapshot of the competitor's current values. */
    public function record(Competitor $competitor): void
    {
        self::recordPlace((string) $competitor->place_id, $competitor->rating !== null ? (float) $competitor->rating : null, (int) $competitor->reviews_count);
    }

    /** Record today's snapshot for a raw place (shared central row). */
    public static function recordPlace(string $placeId, ?float $rating, int $reviewsCount): void
    {
        // A Carbon instance (not a "Y-m-d" string) so the WHERE binding matches
        // the stored value under both sqlite and mysql date handling.
        PlaceSnapshot::updateOrCreate(
            ['place_id' => $placeId, 'day' => CarbonImmutable::today()->startOfDay()],
            ['rating' => $rating, 'reviews_count' => $reviewsCount],
        );
    }

    /**
     * Delta + sparkline for one competitor since $start, plus the same delta
     * for the PREVIOUS window of equal length (period comparison). Deltas are
     * null until enough snapshots exist.
     *
     * @return array{reviews_delta: ?int, rating_delta: ?float, prev_reviews_delta: ?int, spark: list<int>}
     */
    public function summary(Competitor $competitor, CarbonImmutable $start, ?CarbonImmutable $end = null): array
    {
        $end ??= CarbonImmutable::today()->endOfDay();

        /** @var Collection<int, PlaceSnapshot> $snapshots */
        $snapshots = PlaceSnapshot::query()
            ->where('place_id', $competitor->place_id)
            ->orderBy('day')
            ->get();

        if ($snapshots->isEmpty()) {
            return ['reviews_delta' => null, 'rating_delta' => null, 'prev_reviews_delta' => null, 'spark' => []];
        }

        // Baseline: the last snapshot at/before the window start, else the
        // earliest inside it. Current: the latest snapshot at/before the end.
        $baseline = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($start))
            ?? $snapshots->first(fn (PlaceSnapshot $s): bool => $s->day->gt($start) && $s->day->lte($end));
        $current = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($end));

        $comparable = $baseline !== null && $current !== null && ! $baseline->is($current);

        // Previous window of the same length, strictly snapshot-based.
        $prevStart = $start->subDays(max(1, (int) $start->diffInDays($end)));
        $atPrevStart = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($prevStart));
        $atStart = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($start));

        $inWindow = $snapshots->filter(fn (PlaceSnapshot $s): bool => $s->day->gte($start) && $s->day->lte($end));

        return [
            'reviews_delta' => $comparable ? $current->reviews_count - $baseline->reviews_count : null,
            'rating_delta' => $comparable && $baseline->rating !== null && $current->rating !== null
                ? round((float) $current->rating - (float) $baseline->rating, 2)
                : null,
            'prev_reviews_delta' => ($atPrevStart !== null && $atStart !== null && ! $atPrevStart->is($atStart))
                ? $atStart->reviews_count - $atPrevStart->reviews_count
                : null,
            'spark' => $inWindow->pluck('reviews_count')->map(fn ($v): int => (int) $v)->values()->all(),
        ];
    }

    /** The own location's exact new-review count in the window. */
    public function ownNewReviews(int $locationId, CarbonImmutable $start, ?CarbonImmutable $end = null): int
    {
        return Review::query()
            ->where('location_id', $locationId)
            ->where('created_at_external', '>=', $start)
            ->when($end !== null, fn ($q) => $q->where('created_at_external', '<=', $end))
            ->count();
    }

    /** New-review count across several own locations in the window. */
    public function ownNewReviewsForMany(array $locationIds, CarbonImmutable $start, ?CarbonImmutable $end = null): int
    {
        $locationIds = array_values(array_filter(array_map('intval', $locationIds)));
        if ($locationIds === []) {
            return 0;
        }

        return Review::query()
            ->whereIn('location_id', $locationIds)
            ->where('created_at_external', '>=', $start)
            ->when($end !== null, fn ($q) => $q->where('created_at_external', '<=', $end))
            ->count();
    }

    /**
     * Aggregate trend across a GROUP of competitor places (a battle's side):
     * reviews delta and the previous-window delta are summed over the places;
     * the sparkline is the total review count per day across them.
     *
     * @param  list<string>  $placeIds
     * @return array{reviews_delta: ?int, rating_delta: ?float, prev_reviews_delta: ?int, spark: list<int>}
     */
    public function placesSummary(array $placeIds, CarbonImmutable $start, ?CarbonImmutable $end = null): array
    {
        $placeIds = array_values(array_unique(array_filter($placeIds)));
        if ($placeIds === []) {
            return ['reviews_delta' => null, 'rating_delta' => null, 'prev_reviews_delta' => null, 'spark' => []];
        }

        $end ??= CarbonImmutable::today()->endOfDay();

        $reviewsDelta = 0;
        $prevReviewsDelta = 0;
        $anyReviews = false;
        $anyPrev = false;

        // Weighted rating deltas: Σ(ratingΔ × current reviews) / Σ(current reviews).
        $ratingWeighted = 0.0;
        $ratingWeight = 0;
        $anyRating = false;

        /** @var array<string, int> $sparkByDay day => summed review count */
        $sparkByDay = [];

        foreach ($placeIds as $placeId) {
            /** @var Collection<int, PlaceSnapshot> $snapshots */
            $snapshots = PlaceSnapshot::query()->where('place_id', $placeId)->orderBy('day')->get();
            if ($snapshots->isEmpty()) {
                continue;
            }

            $baseline = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($start))
                ?? $snapshots->first(fn (PlaceSnapshot $s): bool => $s->day->gt($start) && $s->day->lte($end));
            $current = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($end));
            $comparable = $baseline !== null && $current !== null && ! $baseline->is($current);

            if ($comparable) {
                $anyReviews = true;
                $reviewsDelta += $current->reviews_count - $baseline->reviews_count;

                if ($baseline->rating !== null && $current->rating !== null) {
                    $anyRating = true;
                    $ratingWeighted += ((float) $current->rating - (float) $baseline->rating) * (int) $current->reviews_count;
                    $ratingWeight += (int) $current->reviews_count;
                }
            }

            $prevStart = $start->subDays(max(1, (int) $start->diffInDays($end)));
            $atPrevStart = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($prevStart));
            $atStart = $snapshots->last(fn (PlaceSnapshot $s): bool => $s->day->lte($start));
            if ($atPrevStart !== null && $atStart !== null && ! $atPrevStart->is($atStart)) {
                $anyPrev = true;
                $prevReviewsDelta += $atStart->reviews_count - $atPrevStart->reviews_count;
            }

            foreach ($snapshots->filter(fn (PlaceSnapshot $s): bool => $s->day->gte($start) && $s->day->lte($end)) as $s) {
                $key = $s->day->toDateString();
                $sparkByDay[$key] = ($sparkByDay[$key] ?? 0) + (int) $s->reviews_count;
            }
        }

        ksort($sparkByDay);

        return [
            'reviews_delta' => $anyReviews ? $reviewsDelta : null,
            'rating_delta' => $anyRating && $ratingWeight > 0 ? round($ratingWeighted / $ratingWeight, 2) : null,
            'prev_reviews_delta' => $anyPrev ? $prevReviewsDelta : null,
            'spark' => array_values($sparkByDay),
        ];
    }

    /**
     * Weighted-by-reviews rating over a set of {rating, reviews} rows:
     * Σ(rating × reviews) / Σ(reviews). Null when there is no rated volume.
     *
     * @param  iterable<array{rating: ?float, reviews_count: int}>  $items
     */
    public static function weightedRating(iterable $items): ?float
    {
        $sum = 0.0;
        $weight = 0;

        foreach ($items as $item) {
            $rating = $item['rating'] ?? null;
            $reviews = (int) ($item['reviews_count'] ?? 0);
            if ($rating === null || $reviews <= 0) {
                continue;
            }
            $sum += (float) $rating * $reviews;
            $weight += $reviews;
        }

        return $weight > 0 ? round($sum / $weight, 2) : null;
    }

    /**
     * Daily review GROWTH lines for a battle chart: one series per competitor
     * place plus the own side, all rebased to 0 at the window start so a
     * 7,000-review incumbent and a 300-review own profile share one scale.
     * Competitor values carry forward over snapshot gaps and are null before
     * the first known snapshot; the own line is exact (reviews table).
     *
     * @param  list<string>  $placeIds
     * @param  list<int>  $ownLocationIds
     * @return array{labels: list<string>, own: list<int>, places: array<string, list<int|null>>}
     */
    public function growthSeries(array $placeIds, array $ownLocationIds, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $end = $end->min(CarbonImmutable::today()->endOfDay());
        $days = [];
        for ($d = $start->startOfDay(); $d->lte($end); $d = $d->addDay()) {
            $days[] = $d;
        }
        if ($days === []) {
            return ['labels' => [], 'own' => [], 'places' => []];
        }

        // Thin long windows so the chart stays readable (~90 points max).
        $step = (int) max(1, ceil(count($days) / 90));
        if ($step > 1) {
            $lastIndex = count($days) - 1;
            $days = array_values(array_filter(
                $days,
                fn ($d, $i): bool => $i % $step === 0 || $i === $lastIndex,
                ARRAY_FILTER_USE_BOTH,
            ));
        }

        $labels = array_map(fn (CarbonImmutable $d): string => $d->format('M j'), $days);

        // Own side: exact cumulative new reviews per day.
        $ownLocationIds = array_values(array_filter(array_map('intval', $ownLocationIds)));
        $perDay = $ownLocationIds === [] ? collect() : Review::query()
            ->whereIn('location_id', $ownLocationIds)
            ->whereBetween('created_at_external', [$start, $end])
            ->get(['created_at_external'])
            ->groupBy(fn (Review $r): string => optional($r->created_at_external)->format('Y-m-d') ?? '')
            ->map->count();

        $own = [];
        $running = 0;
        $cursor = $start->startOfDay();
        foreach ($days as $day) {
            // Sum every real day up to this (possibly thinned) point.
            while ($cursor->lte($day)) {
                $running += (int) ($perDay[$cursor->format('Y-m-d')] ?? 0);
                $cursor = $cursor->addDay();
            }
            $own[] = $running;
        }

        // Competitors: snapshot growth vs the window baseline, carried forward.
        $placeIds = array_values(array_unique(array_filter($placeIds)));
        $snapshots = PlaceSnapshot::query()
            ->whereIn('place_id', $placeIds)
            ->where('day', '<=', $end)
            ->orderBy('day')
            ->get()
            ->groupBy('place_id');

        $places = [];
        foreach ($placeIds as $placeId) {
            /** @var Collection<int, PlaceSnapshot> $rows */
            $rows = $snapshots->get($placeId, collect());
            $baseline = $rows->last(fn (PlaceSnapshot $sn): bool => $sn->day->lte($start))
                ?? $rows->first(fn (PlaceSnapshot $sn): bool => $sn->day->lte($end));

            $series = [];
            foreach ($days as $day) {
                $latest = $rows->last(fn (PlaceSnapshot $sn): bool => $sn->day->lte($day->endOfDay()));
                $series[] = ($latest !== null && $baseline !== null)
                    ? max(0, $latest->reviews_count - $baseline->reviews_count)
                    : null;
            }
            $places[$placeId] = $series;
        }

        return ['labels' => $labels, 'own' => $own, 'places' => $places];
    }
}
