<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Review;
use App\Support\DashboardPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Collection cadence" analysis: are reviews spread out over time, or do they
 * arrive in tight same-session bursts that Google's anti-manipulation filter
 * targets? Pure computation from review timestamps — no AI, no config.
 */
class CadenceAnalyzer
{
    /** Reviews landing within this many minutes count as one "burst". */
    private const BURST_WINDOW_MINUTES = 10;

    /** A burst needs at least this many reviews to be worth flagging. */
    private const BURST_MIN = 3;

    /**
     * @return array{
     *   daily: array<int, array{date: string, label: string, dow: string, count: int, level: string}>,
     *   activeDays: int, totalDays: int, perActiveDay: float, busiestCount: int,
     *   bursts: array<int, array{date: string, window: string, count: int, flag: string}>
     * }
     */
    public function analyze(DashboardPeriod $period): array
    {
        $reviews = $this->window($period)
            ->whereNotNull('created_at_external')
            ->orderBy('created_at_external')
            ->get(['created_at_external']);

        // Per-day buckets across the whole window.
        $start = $period->start->copy()->startOfDay();
        $end = $period->end->copy()->startOfDay();
        $totalDays = (int) $start->diffInDays($end) + 1;

        $counts = [];
        for ($i = 0; $i < $totalDays; $i++) {
            $counts[$start->copy()->addDays($i)->format('Y-m-d')] = 0;
        }
        foreach ($reviews as $r) {
            $key = $r->created_at_external->format('Y-m-d');
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        $daily = [];
        foreach ($counts as $date => $count) {
            $d = CarbonImmutable::parse($date);
            $daily[] = [
                'date' => $date,
                'label' => $d->format('d'),
                'dow' => $d->format('D'),
                'count' => $count,
                'level' => $this->level($count),
            ];
        }

        $activeDays = count(array_filter($counts, fn (int $c): bool => $c > 0));
        $totalReviews = array_sum($counts);

        return [
            'daily' => $daily,
            'activeDays' => $activeDays,
            'totalDays' => $totalDays,
            'perActiveDay' => $activeDays > 0 ? round($totalReviews / $activeDays, 1) : 0.0,
            'busiestCount' => $counts ? max($counts) : 0,
            'bursts' => $this->bursts($reviews),
        ];
    }

    /** Heatmap colour level: grey=0, green=1–2, amber=3–4, red=5+ (clustering). */
    private function level(int $count): string
    {
        return match (true) {
            $count === 0 => 'none',
            $count <= 2 => 'low',
            $count <= 4 => 'mid',
            default => 'high',
        };
    }

    /**
     * Find same-session bursts: runs of >= BURST_MIN reviews that all fall within
     * a BURST_WINDOW_MINUTES span (the tightest filtering risk).
     *
     * @param  Collection<int, Review>  $reviews  ordered by time
     * @return array<int, array{date: string, window: string, count: int, flag: string}>
     */
    private function bursts(Collection $reviews): array
    {
        $times = $reviews->pluck('created_at_external')->all();
        $bursts = [];
        $n = count($times);
        $i = 0;

        while ($i < $n) {
            $j = $i;
            while ($j + 1 < $n && $times[$i]->diffInMinutes($times[$j + 1]) <= self::BURST_WINDOW_MINUTES) {
                $j++;
            }

            $count = $j - $i + 1;
            if ($count >= self::BURST_MIN) {
                $first = $times[$i];
                $last = $times[$j];
                $spanMin = $first->diffInMinutes($last);
                $bursts[] = [
                    'date' => $first->format('D, M j'),
                    'window' => $spanMin === 0
                        ? $first->format('H:i').' (same minute)'
                        : $first->format('H:i').'-'.$last->format('H:i'),
                    'count' => $count,
                    'flag' => ($spanMin <= 5 || $count >= 5) ? 'high' : 'medium',
                ];
                $i = $j + 1;

                continue;
            }

            $i++;
        }

        // Worst bursts first.
        usort($bursts, fn ($a, $b): int => $b['count'] <=> $a['count']);

        return $bursts;
    }

    private function window(DashboardPeriod $period): Builder
    {
        return Review::query()
            ->when($period->locationIds !== [], fn (Builder $q): Builder => $q->whereIn('location_id', $period->locationIds))
            ->whereBetween('created_at_external', [$period->start, $period->end]);
    }
}
