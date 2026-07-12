<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Location;
use App\Models\Review;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class RatingTrendChart extends ChartWidget
{
    use Concerns\HasUserGridSpan;
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected ?string $heading = 'Rating & volume trend';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return tenancy()->initialized && DashboardWidgets::visible('rating_trend');
    }

    protected function getData(): array
    {
        // No location yet → demo data behind the connect-first overlay.
        if (DemoDashboard::active()) {
            $demo = DemoDashboard::trend();

            return [
                'datasets' => [
                    [
                        'label' => 'Avg rating',
                        'data' => $demo['ratings'],
                        'borderColor' => '#2563eb',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                        'spanGaps' => true,
                        'tension' => 0.3,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Reviews',
                        'data' => $demo['volumes'],
                        'type' => 'bar',
                        'borderColor' => '#d1d5db',
                        'backgroundColor' => 'rgba(156, 163, 175, 0.35)',
                        'yAxisID' => 'y1',
                    ],
                ],
                'labels' => $demo['labels'],
            ];
        }

        $period = DashboardPeriod::fromFilters($this->pageFilters);

        // Adaptive bucket width: daily for short windows, weekly otherwise.
        $bucketDays = $period->days() <= 31 ? 1 : 7;
        $bucketCount = (int) ceil($period->days() / $bucketDays);

        [$avgCur, $volCur, $labels] = $this->bucket($period, $period->start, $bucketDays, $bucketCount, withLabels: true);

        $datasets = [
            [
                'label' => 'Avg rating',
                'data' => $avgCur,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                'spanGaps' => true,
                'tension' => 0.3,
                'yAxisID' => 'y',
            ],
            [
                'label' => 'Reviews',
                'data' => $volCur,
                'type' => 'bar',
                'borderColor' => '#d1d5db',
                'backgroundColor' => 'rgba(156, 163, 175, 0.35)',
                'yAxisID' => 'y1',
            ],
        ];

        if ($period->compare) {
            [$avgPrev] = $this->bucket($period, $period->prevStart, $bucketDays, $bucketCount, withLabels: false);
            $datasets[] = [
                'label' => 'Avg rating (previous)',
                'data' => $avgPrev,
                'borderColor' => '#9ca3af',
                'borderDash' => [5, 4],
                'backgroundColor' => 'transparent',
                'spanGaps' => true,
                'tension' => 0.3,
                'yAxisID' => 'y',
            ];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    /**
     * Bucket reviews from $from into $bucketCount slots of $bucketDays each.
     *
     * @return array{0: array<int, float|null>, 1: array<int, int>, 2: array<int, string>}
     */
    protected function bucket(DashboardPeriod $period, CarbonImmutable $from, int $bucketDays, int $bucketCount, bool $withLabels): array
    {
        $to = $from->addDays($bucketCount * $bucketDays);

        $reviews = Review::query()
            ->when($period->locationIds !== [], fn (Builder $q): Builder => $q->whereIn('location_id', $period->locationIds))
            ->whereBetween('created_at_external', [$from, $to])
            ->get(['rating', 'created_at_external']);

        $sum = array_fill(0, $bucketCount, 0);
        $cnt = array_fill(0, $bucketCount, 0);

        foreach ($reviews as $review) {
            if ($review->created_at_external === null) {
                continue;
            }
            $idx = (int) floor($from->diffInDays($review->created_at_external) / $bucketDays);
            $idx = max(0, min($bucketCount - 1, $idx));
            $sum[$idx] += $review->rating;
            $cnt[$idx]++;
        }

        $avg = [];
        $vol = [];
        $labels = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $avg[$i] = $cnt[$i] > 0 ? round($sum[$i] / $cnt[$i], 2) : null;
            $vol[$i] = $cnt[$i];
            if ($withLabels) {
                $labels[$i] = $from->addDays($i * $bucketDays)->format('M j');
            }
        }

        return [$avg, $vol, $labels];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['position' => 'left', 'min' => 0, 'max' => 5, 'title' => ['display' => true, 'text' => 'Rating']],
                'y1' => ['position' => 'right', 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }
}
