<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Competitor;
use App\Models\Location;
use App\Models\Review;
use App\Services\Competitors\CompetitorTrends;
use App\Support\DashboardPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pure-metrics computation for the Monthly Performance Report. No AI, no
 * rendering, just the numbers/series the report (screen + PDF) needs, scoped
 * to a DashboardPeriod (location + date window) with previous-period deltas.
 */
class ReportData
{
    public function __construct(
        private readonly CadenceAnalyzer $cadenceAnalyzer,
        private readonly ResponsePerformanceAnalyzer $responsePerformanceAnalyzer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(DashboardPeriod $period): array
    {
        $cur = $this->window($period, $period->start, $period->end);
        $prev = $this->window($period, $period->prevStart, $period->prevEnd);

        $total = (int) $cur->count();
        $avg = (float) ($cur->avg('rating') ?? 0);
        $replied = (int) $cur->whereNotNull('reply_text')->count();
        $rate = $total > 0 ? (int) round($replied / $total * 100) : 0;

        $pTotal = (int) $prev->count();
        $pAvg = (float) ($prev->avg('rating') ?? 0);
        $pReplied = (int) $prev->whereNotNull('reply_text')->count();
        $pRate = $pTotal > 0 ? (int) round($pReplied / $pTotal * 100) : 0;

        // Star distribution (5..1).
        $byStar = $this->window($period, $period->start, $period->end)
            ->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $distribution = [];
        for ($s = 5; $s >= 1; $s--) {
            $distribution[$s] = (int) ($byStar[$s] ?? 0);
        }

        // Volume series (adaptive: daily for short windows, weekly otherwise).
        $series = $this->volumeSeries($period);

        // Busiest day (a proxy for the PDF's "burst" highlight).
        $perDay = $this->window($period, $period->start, $period->end)
            ->get(['created_at_external'])
            ->groupBy(fn (Review $r): string => optional($r->created_at_external)->format('Y-m-d') ?? '—');
        $busiest = $perDay->map->count()->sortDesc()->take(1);

        return [
            'businessName' => $this->businessName($period),
            'allLocations' => $period->locationId === null && Location::query()->count() > 1,
            'periodLabel' => $period->label(),
            'previousLabel' => $period->previousLabel(),
            'compare' => $period->compare,
            'kpis' => [
                'total' => ['value' => $total, 'prev' => $pTotal, 'delta' => $total - $pTotal],
                'avg' => ['value' => round($avg, 2), 'prev' => round($pAvg, 2), 'delta' => round($avg - $pAvg, 2)],
                'responseRate' => ['value' => $rate, 'prev' => $pRate, 'delta' => $rate - $pRate],
                'replied' => ['value' => $replied, 'prev' => $pReplied, 'delta' => $replied - $pReplied],
            ],
            'distribution' => $distribution,
            'fiveStarShare' => $total > 0 ? (int) round($distribution[5] / $total * 100) : 0,
            'positivePct' => $total > 0 ? (int) round((($distribution[5] + $distribution[4]) / $total) * 100) : 0,
            'negativePct' => $total > 0 ? (int) round((($distribution[2] + $distribution[1]) / $total) * 100) : 0,
            'series' => $series,
            'cadence' => $this->cadenceAnalyzer->analyze($period),
            'responses' => $this->responsePerformanceAnalyzer->analyze($period),
            'busiestDay' => $busiest->keys()->first(),
            'busiestCount' => (int) ($busiest->first() ?? 0),
            'competitors' => $this->competitors($period),
            'highlightsPositive' => $this->highlights($period, [5, 4], 3),
            'highlightsCritical' => $this->highlights($period, [1, 2], 3),
            'reviewSnippets' => $this->snippets($period, 40),
        ];
    }

    /** Base query for a window, scoped to the period's location. */
    /**
     * Competitor standing for the report period: current rating/reviews plus
     * the review growth inside the window (from the daily snapshots; null
     * while history is still being collected). Empty array = no competitors
     * tracked → the block is skipped.
     *
     * @return array{own: array{name: string, rating: ?float, reviews: int, new_reviews: int}, rows: list<array{name: string, rating: ?float, reviews: int, new_reviews: ?int}>}|array{}
     */
    protected function competitors(DashboardPeriod $period): array
    {
        $competitors = Competitor::query()
            ->when($period->locationId !== null, fn ($q) => $q->where('location_id', $period->locationId))
            ->orderByDesc('rating')
            ->get();

        if ($competitors->isEmpty()) {
            return [];
        }

        $trends = app(CompetitorTrends::class);
        $start = $period->start;

        $locations = Location::query()
            ->when($period->locationId !== null, fn ($q) => $q->whereKey($period->locationId))
            ->get();

        $ownRatings = $locations->pluck('rating')->filter();

        return [
            'own' => [
                'name' => $this->businessName($period),
                'rating' => $ownRatings->isNotEmpty() ? round((float) $ownRatings->avg(), 1) : null,
                'reviews' => (int) $locations->sum('reviews_count'),
                'new_reviews' => (int) $locations->sum(fn (Location $l): int => $trends->ownNewReviews((int) $l->id, $start)),
            ],
            'rows' => $competitors->map(fn (Competitor $c): array => [
                'name' => $c->name,
                'rating' => $c->rating !== null ? (float) $c->rating : null,
                'reviews' => (int) $c->reviews_count,
                'new_reviews' => $trends->summary($c, $start)['reviews_delta'],
            ])->all(),
        ];
    }

    protected function window(DashboardPeriod $period, $from, $to): Builder
    {
        return Review::query()
            ->when($period->locationId, fn (Builder $q, int $id): Builder => $q->where('location_id', $id))
            ->whereBetween('created_at_external', [$from, $to]);
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, granularity: string}
     */
    protected function volumeSeries(DashboardPeriod $period): array
    {
        $bucketDays = $period->days() <= 45 ? 1 : 7;
        $bucketCount = (int) ceil($period->days() / $bucketDays);

        $reviews = $this->window($period, $period->start, $period->start->addDays($bucketCount * $bucketDays))
            ->get(['created_at_external']);

        $counts = array_fill(0, $bucketCount, 0);
        foreach ($reviews as $r) {
            if ($r->created_at_external === null) {
                continue;
            }
            $idx = (int) floor($period->start->diffInDays($r->created_at_external) / $bucketDays);
            $idx = max(0, min($bucketCount - 1, $idx));
            $counts[$idx]++;
        }

        $labels = [];
        $titles = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $bucketStart = $period->start->addDays($i * $bucketDays);
            $labels[] = $bucketStart->format('M j');
            // Tooltip title: weekday for daily buckets, week range for weekly.
            $titles[] = $bucketDays === 1
                ? $bucketStart->format('D, M j')
                : $bucketStart->format('M j').' to '.$bucketStart->addDays(6)->format('M j');
        }

        return ['labels' => $labels, 'titles' => $titles, 'data' => $counts, 'granularity' => $bucketDays === 1 ? 'day' : 'week'];
    }

    /**
     * @param  array<int, int>  $ratings
     * @return Collection<int, Review>
     */
    protected function highlights(DashboardPeriod $period, array $ratings, int $limit): Collection
    {
        return $this->window($period, $period->start, $period->end)
            ->whereIn('rating', $ratings)
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->with('location:id,name')
            ->latest('created_at_external')
            ->limit($limit)
            ->get(['id', 'location_id', 'author_name', 'rating', 'text', 'created_at_external']);
    }

    /**
     * Review snippets for the AI insights call. The per-review cut is generous
     * (1200 chars): a tighter cut silently dropped staff names mentioned deep
     * in detailed reviews, breaking the staff-mention counts.
     */
    protected function snippets(DashboardPeriod $period, int $limit): array
    {
        return $this->window($period, $period->start, $period->end)
            ->whereNotNull('text')->where('text', '!=', '')
            ->latest('created_at_external')
            ->limit($limit)
            ->get(['author_name', 'rating', 'text'])
            ->map(fn (Review $r): array => [
                'author' => $r->author_name,
                'rating' => $r->rating,
                'text' => mb_substr((string) ($r->originalText() ?? $r->text), 0, 1200),
            ])->all();
    }

    protected function businessName(DashboardPeriod $period): string
    {
        if ($period->locationId) {
            return Location::query()->whereKey($period->locationId)->value('name') ?: 'All locations';
        }

        $names = Location::query()->orderBy('name')->pluck('name');

        return $names->count() === 1 ? (string) $names->first() : 'All locations';
    }
}
