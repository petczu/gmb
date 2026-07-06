<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Competitor;
use App\Models\CompetitorSnapshot;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Trends over the daily competitor snapshots: review/rating deltas for a
 * period and a sparkline series. The own location's growth comes straight
 * from the reviews table (exact, date-based).
 */
class CompetitorTrends
{
    /** Record today's snapshot of the competitor's current values. */
    public function record(Competitor $competitor): void
    {
        // A Carbon instance (not a "Y-m-d" string) so the WHERE binding matches
        // the stored value under both sqlite and mysql date handling.
        CompetitorSnapshot::updateOrCreate(
            ['competitor_id' => $competitor->id, 'day' => CarbonImmutable::today()->startOfDay()],
            ['rating' => $competitor->rating, 'reviews_count' => $competitor->reviews_count],
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

        /** @var Collection<int, CompetitorSnapshot> $snapshots */
        $snapshots = CompetitorSnapshot::query()
            ->where('competitor_id', $competitor->id)
            ->orderBy('day')
            ->get();

        if ($snapshots->isEmpty()) {
            return ['reviews_delta' => null, 'rating_delta' => null, 'prev_reviews_delta' => null, 'spark' => []];
        }

        // Baseline: the last snapshot at/before the window start, else the
        // earliest inside it. Current: the latest snapshot at/before the end.
        $baseline = $snapshots->last(fn (CompetitorSnapshot $s): bool => $s->day->lte($start))
            ?? $snapshots->first(fn (CompetitorSnapshot $s): bool => $s->day->gt($start) && $s->day->lte($end));
        $current = $snapshots->last(fn (CompetitorSnapshot $s): bool => $s->day->lte($end));

        $comparable = $baseline !== null && $current !== null && ! $baseline->is($current);

        // Previous window of the same length, strictly snapshot-based.
        $prevStart = $start->subDays(max(1, (int) $start->diffInDays($end)));
        $atPrevStart = $snapshots->last(fn (CompetitorSnapshot $s): bool => $s->day->lte($prevStart));
        $atStart = $snapshots->last(fn (CompetitorSnapshot $s): bool => $s->day->lte($start));

        $inWindow = $snapshots->filter(fn (CompetitorSnapshot $s): bool => $s->day->gte($start) && $s->day->lte($end));

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
}
