<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Models\Location;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Computes review-growth signals for the CURRENT tenant (must run inside an
 * initialized tenancy). Pure read-only analysis — no mail, no persistence — so
 * it is straightforward to unit-test against a seeded tenant. Everything is
 * measured from the review's real publication date (created_at_external) and
 * its rating.
 *
 * @phpstan-type GoalRow array{location_id: int, location: string, goal: int, actual: int, expected: int, projected: int, status: string}
 * @phpstan-type Anomaly array{location_id: int, location: string, type: string, detail: array<string, int|float>}
 */
class ReviewInsightsService
{
    /** A location silent this many days, while normally active, is "stalled". */
    private const STALL_DAYS = 14;

    /** ...where "normally active" means at least this many reviews in the window below. */
    private const STALL_MIN_HISTORY = 6;

    private const STALL_HISTORY_DAYS = 90;

    /** N reviews at or below MAX_RATING within HOURS counts as a negative streak. */
    private const NEGATIVE_STREAK_HOURS = 72;

    private const NEGATIVE_STREAK_COUNT = 3;

    private const NEGATIVE_MAX_RATING = 2;

    /** Spike: last 7 days >= MULTIPLIER x the normal weekly rate (and a floor). */
    private const SPIKE_RECENT_DAYS = 7;

    private const SPIKE_BASELINE_WEEKS = 8;

    private const SPIKE_MIN_BASELINE_WEEKLY = 2.0;

    private const SPIKE_MULTIPLIER = 3.0;

    private const SPIKE_MIN_RECENT = 6;

    /** Rating drop: 30-day average down by DROP vs the prior 30 days. */
    private const RATING_WINDOW_DAYS = 30;

    private const RATING_MIN_SAMPLE = 5;

    private const RATING_DROP = 0.3;

    /** Pace bands for goal progress (fraction of the pro-rated expectation). */
    private const PACE_AHEAD = 1.1;

    private const PACE_BEHIND = 0.8;

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }

    /** True when at least one location has a monthly goal set. */
    public function hasAnyGoal(): bool
    {
        return Location::query()->where('review_goal', '>', 0)->exists();
    }

    /**
     * Month-to-date goal progress for every location that has a goal, plus a
     * workspace-wide rollup and pace status.
     *
     * @return array{rows: list<GoalRow>, total_goal: int, total_actual: int, total_expected: int, status: string, day: int, days_in_month: int}
     */
    public function goalProgress(): array
    {
        $now = $this->now();
        $start = $now->startOfMonth();
        $day = $now->day;
        $daysInMonth = $now->daysInMonth;

        $rows = [];
        $totalGoal = 0;
        $totalActual = 0;
        $totalExpected = 0;

        foreach (Location::query()->where('review_goal', '>', 0)->orderBy('name')->get() as $location) {
            $goal = (int) $location->review_goal;
            $actual = $this->countReviews((int) $location->id, $start, $now);
            $expected = (int) round($goal * ($day / max(1, $daysInMonth)));
            $projected = $day > 0 ? (int) round($actual / $day * $daysInMonth) : 0;

            $rows[] = [
                'location_id' => (int) $location->id,
                'location' => (string) $location->name,
                'goal' => $goal,
                'actual' => $actual,
                'expected' => $expected,
                'projected' => $projected,
                'status' => $this->paceStatus($actual, $expected),
            ];

            $totalGoal += $goal;
            $totalActual += $actual;
            $totalExpected += $expected;
        }

        return [
            'rows' => $rows,
            'total_goal' => $totalGoal,
            'total_actual' => $totalActual,
            'total_expected' => $totalExpected,
            'status' => $this->paceStatus($totalActual, $totalExpected),
            'day' => $day,
            'days_in_month' => $daysInMonth,
        ];
    }

    /**
     * A completed-month recap for every location with a goal: how many reviews
     * it got that month, vs goal and vs the month before.
     *
     * @return array{month: string, rows: list<array{location: string, goal: int, actual: int, previous: int, delta: int, percent: ?int}>, total_goal: int, total_actual: int}
     */
    public function recap(CarbonImmutable $month): array
    {
        $monthStart = $month->startOfMonth();
        $monthEnd = $month->endOfMonth();
        $prevStart = $monthStart->subMonth();
        $prevEnd = $monthStart->subSecond();

        $rows = [];
        $totalGoal = 0;
        $totalActual = 0;

        foreach (Location::query()->where('review_goal', '>', 0)->orderBy('name')->get() as $location) {
            $goal = (int) $location->review_goal;
            $actual = $this->countReviews((int) $location->id, $monthStart, $monthEnd);
            $previous = $this->countReviews((int) $location->id, $prevStart, $prevEnd);

            $rows[] = [
                'location' => (string) $location->name,
                'goal' => $goal,
                'actual' => $actual,
                'previous' => $previous,
                'delta' => $actual - $previous,
                'percent' => $goal > 0 ? (int) round($actual / $goal * 100) : null,
            ];

            $totalGoal += $goal;
            $totalActual += $actual;
        }

        return [
            'month' => $monthStart->translatedFormat('F Y'),
            'rows' => $rows,
            'total_goal' => $totalGoal,
            'total_actual' => $totalActual,
        ];
    }

    /**
     * Every anomaly currently detectable across all locations. The caller is
     * responsible for de-duplicating against recently-sent alerts (cooldown).
     *
     * @return list<Anomaly>
     */
    public function anomalies(): array
    {
        $out = [];

        foreach (Location::query()->orderBy('name')->get() as $location) {
            $id = (int) $location->id;
            $name = (string) $location->name;

            if ($detail = $this->detectStalled($id)) {
                $out[] = ['location_id' => $id, 'location' => $name, 'type' => 'stalled', 'detail' => $detail];
            }
            if ($detail = $this->detectNegativeStreak($id)) {
                $out[] = ['location_id' => $id, 'location' => $name, 'type' => 'negative_streak', 'detail' => $detail];
            }
            if ($detail = $this->detectSpike($id)) {
                $out[] = ['location_id' => $id, 'location' => $name, 'type' => 'spike', 'detail' => $detail];
            }
            if ($detail = $this->detectRatingDrop($id)) {
                $out[] = ['location_id' => $id, 'location' => $name, 'type' => 'rating_drop', 'detail' => $detail];
            }
        }

        return $out;
    }

    /** @return array{days: int}|null */
    private function detectStalled(int $locationId): ?array
    {
        $now = $this->now();
        $history = $this->countReviews($locationId, $now->subDays(self::STALL_HISTORY_DAYS), $now);

        if ($history < self::STALL_MIN_HISTORY) {
            return null;
        }

        $last = $this->reviewsQuery($locationId)->max('created_at_external');

        if ($last === null) {
            return null;
        }

        $days = (int) CarbonImmutable::parse($last)->diffInDays($now);

        return $days >= self::STALL_DAYS ? ['days' => $days] : null;
    }

    /** @return array{count: int}|null */
    private function detectNegativeStreak(int $locationId): ?array
    {
        $count = $this->reviewsQuery($locationId)
            ->where('rating', '<=', self::NEGATIVE_MAX_RATING)
            ->where('created_at_external', '>=', $this->now()->subHours(self::NEGATIVE_STREAK_HOURS))
            ->count();

        return $count >= self::NEGATIVE_STREAK_COUNT ? ['count' => $count] : null;
    }

    /** @return array{recent: int, baseline: float}|null */
    private function detectSpike(int $locationId): ?array
    {
        $now = $this->now();
        $recent = $this->countReviews($locationId, $now->subDays(self::SPIKE_RECENT_DAYS), $now);

        if ($recent < self::SPIKE_MIN_RECENT) {
            return null;
        }

        $baselineCount = $this->countReviews(
            $locationId,
            $now->subDays(self::SPIKE_RECENT_DAYS + self::SPIKE_BASELINE_WEEKS * 7),
            $now->subDays(self::SPIKE_RECENT_DAYS),
        );
        $baselineWeekly = $baselineCount / self::SPIKE_BASELINE_WEEKS;

        if ($baselineWeekly < self::SPIKE_MIN_BASELINE_WEEKLY) {
            return null;
        }

        return $recent >= $baselineWeekly * self::SPIKE_MULTIPLIER
            ? ['recent' => $recent, 'baseline' => round($baselineWeekly, 1)]
            : null;
    }

    /** @return array{recent: float, prior: float}|null */
    private function detectRatingDrop(int $locationId): ?array
    {
        $now = $this->now();
        $recent = $this->ratingStats($locationId, $now->subDays(self::RATING_WINDOW_DAYS), $now);
        $prior = $this->ratingStats($locationId, $now->subDays(2 * self::RATING_WINDOW_DAYS), $now->subDays(self::RATING_WINDOW_DAYS));

        if ($recent['count'] < self::RATING_MIN_SAMPLE || $prior['count'] < self::RATING_MIN_SAMPLE) {
            return null;
        }

        return ($prior['avg'] - $recent['avg']) >= self::RATING_DROP
            ? ['recent' => round($recent['avg'], 1), 'prior' => round($prior['avg'], 1)]
            : null;
    }

    /** @return array{count: int, avg: float} */
    private function ratingStats(int $locationId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = $this->reviewsQuery($locationId)
            ->whereBetween('created_at_external', [$from, $to])
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')
            ->first();

        return ['count' => (int) ($row->c ?? 0), 'avg' => (float) ($row->a ?? 0)];
    }

    private function countReviews(int $locationId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $this->reviewsQuery($locationId)
            ->whereBetween('created_at_external', [$from, $to])
            ->count();
    }

    /** @return Builder<Review> */
    private function reviewsQuery(int $locationId): Builder
    {
        return Review::query()->where('location_id', $locationId);
    }

    private function paceStatus(int $actual, int $expected): string
    {
        if ($expected <= 0) {
            return $actual > 0 ? 'ahead' : 'on_track';
        }

        if ($actual >= $expected * self::PACE_AHEAD) {
            return 'ahead';
        }

        if ($actual < $expected * self::PACE_BEHIND) {
            return 'behind';
        }

        return 'on_track';
    }
}
