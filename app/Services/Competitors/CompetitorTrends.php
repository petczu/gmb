<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Competitor;
use App\Models\PlaceReview;
use App\Models\PlaceSnapshot;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
     * Daily review lines for the competitor chart: one series per competitor
     * place plus the own side.
     *
     * $mode 'growth' (default): rebased to 0 at the window start so a
     * 7,000-review incumbent and a 300-review own profile share one scale.
     * $mode 'total': absolute review counts, so the lines sit at their real
     * level and you see standing + growth (legend toggles hide the giants).
     *
     * Competitor values carry forward over snapshot gaps and are null before
     * the first known snapshot; the own line is exact (reviews table).
     *
     * @param  list<string>  $placeIds
     * @param  list<int>  $ownLocationIds
     * @return array{labels: list<string>, own: list<int>, places: array<string, list<int|null>>}
     */
    public function growthSeries(array $placeIds, array $ownLocationIds, CarbonImmutable $start, CarbonImmutable $end, string $mode = 'growth'): array
    {
        $total = $mode === 'total';
        $end = $end->min(CarbonImmutable::today()->endOfDay());
        $days = $this->chartDays($start, $end);
        if ($days === []) {
            return ['labels' => [], 'own' => [], 'places' => []];
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

        // In total mode, seed the own line with the review count already on
        // the books before the window so the line sits at its real level.
        $running = $total && $ownLocationIds !== []
            ? Review::query()->whereIn('location_id', $ownLocationIds)->where('created_at_external', '<', $start)->count()
            : 0;

        $own = [];
        $cursor = $start->startOfDay();
        foreach ($days as $day) {
            // Sum every real day up to this (possibly thinned) point.
            while ($cursor->lte($day)) {
                $running += (int) ($perDay[$cursor->format('Y-m-d')] ?? 0);
                $cursor = $cursor->addDay();
            }
            $own[] = $running;
        }

        // Hide the flat run before the first review (e.g. a location that only
        // opened mid-window): leading zeros become null so the line starts at
        // the first real data point instead of implying "0 reviews".
        $own = $this->hideLeadingZeros($own);

        $placeIds = array_values(array_unique(array_filter($placeIds)));

        // Prefer the exact per-day history from the individual reviews backfill
        // (place_reviews) — it covers the whole window, unlike snapshots which
        // only exist since we started collecting. Snapshots are the fallback
        // for places that have no backfilled reviews yet.
        // Cover the whole last chart day ($end may be its midnight).
        $reviewStamps = $placeIds === [] ? collect() : PlaceReview::query()
            ->whereIn('place_id', $placeIds)
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '<=', $end->endOfDay())
            ->orderBy('reviewed_at')
            ->get(['place_id', 'reviewed_at'])
            ->groupBy('place_id');

        // True current totals (absolute) from the latest snapshot per place —
        // used to lift the captured-review cumulative up to the real level in
        // Total mode (the backfill only holds the newest ~4490 reviews).
        $latestTotals = $placeIds === [] ? [] : PlaceSnapshot::query()
            ->whereIn('place_id', $placeIds)
            ->where('day', '<=', $end)
            ->orderBy('day')
            ->get(['place_id', 'reviews_count'])
            ->groupBy('place_id')
            ->map(fn (Collection $rows): int => (int) $rows->last()->reviews_count)
            ->all();

        $snapshots = $placeIds === [] ? collect() : PlaceSnapshot::query()
            ->whereIn('place_id', $placeIds)
            ->where('day', '<=', $end)
            ->orderBy('day')
            ->get()
            ->groupBy('place_id');

        $places = [];
        foreach ($placeIds as $placeId) {
            /** @var Collection<int, PlaceReview>|null $reviews */
            $reviews = $reviewStamps->get($placeId);
            if ($reviews !== null && $reviews->isNotEmpty()) {
                $places[$placeId] = $this->placeSeriesFromReviews(
                    $reviews->pluck('reviewed_at')->all(),
                    $days,
                    $start,
                    $total,
                    $latestTotals[$placeId] ?? null,
                );

                continue;
            }

            // Fallback: snapshot growth vs the window baseline, carried forward.
            /** @var Collection<int, PlaceSnapshot> $rows */
            $rows = $snapshots->get($placeId, collect());
            $baseline = $rows->last(fn (PlaceSnapshot $sn): bool => $sn->day->lte($start))
                ?? $rows->first(fn (PlaceSnapshot $sn): bool => $sn->day->lte($end));

            $series = [];
            foreach ($days as $day) {
                $latest = $rows->last(fn (PlaceSnapshot $sn): bool => $sn->day->lte($day->endOfDay()));
                if ($latest === null) {
                    $series[] = null;

                    continue;
                }
                $series[] = $total
                    ? (int) $latest->reviews_count
                    : ($baseline !== null ? max(0, $latest->reviews_count - $baseline->reviews_count) : null);
            }
            $places[$placeId] = $series;
        }

        // Same treatment for competitors: a line only starts once it has data.
        $places = array_map(fn (array $series): array => $this->hideLeadingZeros($series), $places);

        return ['labels' => $labels, 'own' => $own, 'places' => $places];
    }

    /**
     * Own-side chart lines for SEVERAL groups of locations at once, from a
     * single reviews query (calling growthSeries once per line fired one
     * reviews query plus three empty-set place queries per line — the
     * dashboard's competitor-chart N+1). Semantics per line match
     * growthSeries()'s own series exactly.
     *
     * @param  array<int, list<int>>  $lines  index => member location ids
     * @return array<int, list<int|null>> index => series, same order as $lines
     */
    public function ownSeriesForLines(array $lines, CarbonImmutable $start, CarbonImmutable $end, string $mode = 'growth'): array
    {
        $total = $mode === 'total';
        $end = $end->min(CarbonImmutable::today()->endOfDay());
        $days = $this->chartDays($start, $end);

        $lines = array_map(fn (array $ids): array => array_values(array_filter(array_map('intval', $ids))), $lines);
        $allIds = array_values(array_unique(array_merge([], ...$lines)));

        if ($days === [] || $allIds === []) {
            return array_map(fn (): array => [], $lines);
        }

        /** @var array<int, array<string, int>> $perDay location id => [day => new reviews] */
        $perDay = [];
        foreach (Review::query()
            ->whereIn('location_id', $allIds)
            ->whereBetween('created_at_external', [$start, $end])
            ->get(['location_id', 'created_at_external']) as $review) {
            $day = optional($review->created_at_external)->format('Y-m-d') ?? '';
            $perDay[(int) $review->location_id][$day] = ($perDay[(int) $review->location_id][$day] ?? 0) + 1;
        }

        // Total mode: per-location counts already on the books before the
        // window, so each line starts at its real level.
        $baselines = $total ? Review::query()
            ->whereIn('location_id', $allIds)
            ->where('created_at_external', '<', $start)
            ->groupBy('location_id')
            ->selectRaw('`location_id`, count(*) as `aggregate`')
            ->pluck('aggregate', 'location_id')
            ->all() : [];

        $series = [];
        foreach ($lines as $index => $ids) {
            $running = 0;
            foreach ($ids as $id) {
                $running += (int) ($baselines[$id] ?? 0);
            }

            $line = [];
            $cursor = $start->startOfDay();
            foreach ($days as $day) {
                // Sum every real day up to this (possibly thinned) point.
                while ($cursor->lte($day)) {
                    $key = $cursor->format('Y-m-d');
                    foreach ($ids as $id) {
                        $running += (int) ($perDay[$id][$key] ?? 0);
                    }
                    $cursor = $cursor->addDay();
                }
                $line[] = $running;
            }

            $series[$index] = $this->hideLeadingZeros($line);
        }

        return $series;
    }

    /**
     * The window's charted days, thinned so long windows stay readable
     * (~90 points max); the last real day always survives the thinning.
     *
     * @return list<CarbonImmutable>
     */
    private function chartDays(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = [];
        for ($d = $start->startOfDay(); $d->lte($end); $d = $d->addDay()) {
            $days[] = $d;
        }

        $step = (int) max(1, ceil(count($days) / 90));
        if ($step > 1) {
            $lastIndex = count($days) - 1;
            $days = array_values(array_filter(
                $days,
                fn ($d, $i): bool => $i % $step === 0 || $i === $lastIndex,
                ARRAY_FILTER_USE_BOTH,
            ));
        }

        return $days;
    }

    /**
     * Replace a leading run of zeros/nulls with null so a chart line starts at
     * its first real value (a business open only part of the window shouldn't
     * read as a flat "0 reviews" before it existed). A never-positive series is
     * returned unchanged so genuinely empty lines still render at zero.
     *
     * @param  list<int|null>  $series
     * @return list<int|null>
     */
    private function hideLeadingZeros(array $series): array
    {
        if (! collect($series)->contains(fn ($v): bool => $v !== null && $v > 0)) {
            return $series;
        }

        foreach ($series as $i => $value) {
            if ($value === null || $value === 0) {
                $series[$i] = null;
            } else {
                break;
            }
        }

        return $series;
    }

    /**
     * A competitor's per-day line from captured review timestamps (exact).
     * Growth = new reviews since the window start; Total = the real running
     * total, lifted so the captured newest-N cumulative meets the current
     * absolute count from the latest snapshot.
     *
     * @param  list<CarbonInterface|null>  $reviewedAt  review timestamps (ascending)
     * @param  list<CarbonImmutable>  $days
     * @return list<int>
     */
    private function placeSeriesFromReviews(array $reviewedAt, array $days, CarbonImmutable $start, bool $total, ?int $latestTotal): array
    {
        $stamps = [];
        foreach ($reviewedAt as $t) {
            if ($t !== null) {
                $stamps[] = $t->getTimestamp();
            }
        }
        sort($stamps);
        $captured = count($stamps);

        // Reviews already on the books before the window (the growth baseline).
        $startTs = $start->startOfDay()->getTimestamp();
        $baseline = 0;
        foreach ($stamps as $ts) {
            if ($ts < $startTs) {
                $baseline++;
            } else {
                break;
            }
        }

        // Lift factor for Total: real current total minus what we captured.
        $lift = ($latestTotal ?? $captured) - $captured;

        $series = [];
        $ptr = 0;
        $cum = 0;
        foreach ($days as $day) {
            $dayEnd = $day->endOfDay()->getTimestamp();
            while ($ptr < $captured && $stamps[$ptr] <= $dayEnd) {
                $cum++;
                $ptr++;
            }
            $series[] = $total ? $lift + $cum : max(0, $cum - $baseline);
        }

        return $series;
    }
}
