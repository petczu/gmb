<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Services\Listings\ListingPerformance;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Daily GBP performance line chart with a metric switcher (views, calls,
 * directions, website clicks, bookings) — the widget's built-in filter
 * dropdown. Buckets weekly for long windows.
 */
class ListingPerformanceChart extends ChartWidget
{
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    public ?string $filter = 'views';

    public function getHeading(): ?string
    {
        // The KPI row above carries the section heading; keep the chart terse.
        return $this->getFilters()[$this->filter] ?? __('widgets.perf_chart_title');
    }

    public static function canView(): bool
    {
        return DashboardWidgets::visible('performance_chart')
            && ListingPerformanceOverview::dataAvailable();
    }

    protected function getFilters(): ?array
    {
        return [
            'views' => __('widgets.perf_views'),
            'calls' => __('widgets.perf_calls'),
            'directions' => __('widgets.perf_directions'),
            'website_clicks' => __('widgets.perf_website'),
            'bookings' => __('widgets.perf_bookings'),
        ];
    }

    protected function getData(): array
    {
        $metric = array_key_exists((string) $this->filter, $this->getFilters() ?? []) ? (string) $this->filter : 'views';

        if (DemoDashboard::active()) {
            $demo = DemoDashboard::performance();
            $labels = $demo['labels'];
            // Scale the demo views wave down for the smaller metrics.
            $values = $metric === 'views'
                ? $demo['series']
                : array_map(fn (int $v): int => max(0, (int) round($v / 8)), $demo['series']);
        } else {
            $period = DashboardPeriod::fromFilters($this->pageFilters);
            $metrics = app(ListingPerformance::class)->metrics($period->locationId, $period->start, $period->end);
            $daily = ListingPerformance::dailySeries($metrics['series'], $period->start, $period->end, $metric);

            [$labels, $values] = $this->bucket($daily['labels'], $daily['values'], $period->days());
        }

        return [
            'datasets' => [
                [
                    'label' => $this->getFilters()[$metric] ?? $metric,
                    'data' => $values,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Weekly sums for long windows so the line stays readable.
     *
     * @param  list<string>  $labels
     * @param  list<int>  $values
     * @return array{0: list<string>, 1: list<int>}
     */
    private function bucket(array $labels, array $values, int $days): array
    {
        if ($days <= 45) {
            return [$labels, $values];
        }

        $bucketLabels = [];
        $bucketValues = [];

        foreach (array_chunk($values, 7) as $i => $chunk) {
            $bucketLabels[] = $labels[$i * 7] ?? '';
            $bucketValues[] = array_sum($chunk);
        }

        return [$bucketLabels, $bucketValues];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
