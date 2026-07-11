<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Location;
use App\Services\Listings\ListingPerformance;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Google Business Profile performance KPIs (views, calls, bookings,
 * directions, website clicks) for the dashboard period, with percent deltas
 * against the previous window. Data comes from ListingPerformance (cached).
 */
class ListingPerformanceOverview extends StatsOverviewWidget
{
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    /** Half width: the KPI 2×2 sits left of the performance chart. */
    protected int|string|array $columnSpan = 1;

    protected function getColumns(): int|array|null
    {
        return 2;
    }

    protected function getHeading(): ?string
    {
        return __('widgets.perf_chart_title');
    }

    protected function getDescription(): ?string
    {
        return __('widgets.perf_lag_note');
    }

    public static function canView(): bool
    {
        if (! tenancy()->initialized || ! DashboardWidgets::visible('performance')) {
            return false;
        }

        return DemoDashboard::active()
            || (app(ListingPerformance::class)->configured()
                && Location::query()->whereNotNull('zernio_account_id')->exists());
    }

    /** Shared data-availability check for the two sibling performance widgets. */
    public static function dataAvailable(): bool
    {
        if (! tenancy()->initialized) {
            return false;
        }

        return DemoDashboard::active()
            || (app(ListingPerformance::class)->configured()
                && Location::query()->whereNotNull('zernio_account_id')->exists());
    }

    protected function getStats(): array
    {
        if (DemoDashboard::active()) {
            $demo = DemoDashboard::performance();

            return $this->cards($demo['views'], $demo['totals'], prevViews: 1080, prevTotals: [
                'calls' => 71, 'directions' => 275, 'website_clicks' => 412,
            ]);
        }

        $period = DashboardPeriod::fromFilters($this->pageFilters);
        $performance = app(ListingPerformance::class);

        $current = $performance->metrics($period->locationIds, $period->start, $period->end);

        if (! $current['available']) {
            return [
                Stat::make(__('widgets.perf_chart_title'), '—')
                    ->description(__('widgets.perf_no_data'))
                    ->color('gray'),
            ];
        }

        $prevViews = null;
        $prevTotals = [];

        if ($period->compare) {
            $previous = $performance->metrics($period->locationIds, $period->prevStart, $period->prevEnd);
            if ($previous['available']) {
                $prevViews = $previous['views'];
                $prevTotals = $previous['totals'];
            }
        }

        return $this->cards($current['views'], $current['totals'], $prevViews, $prevTotals);
    }

    /**
     * @param  array<string, int>  $totals
     * @param  array<string, int>  $prevTotals
     * @return array<int, Stat>
     */
    private function cards(int $views, array $totals, ?int $prevViews, array $prevTotals): array
    {
        $cards = [
            $this->stat('perf_views', $views, $prevViews),
            $this->stat('perf_calls', $totals['calls'] ?? 0, $prevTotals['calls'] ?? null),
            $this->stat('perf_directions', $totals['directions'] ?? 0, $prevTotals['directions'] ?? null),
            $this->stat('perf_website', $totals['website_clicks'] ?? 0, $prevTotals['website_clicks'] ?? null),
        ];

        // Bookings only matter for businesses with Google's booking button —
        // hidden while there is nothing to show (keeps the grid a clean 2×2).
        if (($totals['bookings'] ?? 0) > 0 || ($prevTotals['bookings'] ?? 0) > 0) {
            $cards[] = $this->stat('perf_bookings', $totals['bookings'] ?? 0, $prevTotals['bookings'] ?? null);
        }

        return $cards;
    }

    private function stat(string $key, int $value, ?int $previous): Stat
    {
        $stat = Stat::make(__('widgets.'.$key), number_format($value));

        if ($previous !== null && $previous > 0) {
            $pct = (int) round(($value - $previous) / $previous * 100);

            if ($pct !== 0) {
                return $stat
                    ->description(__('widgets.vs_previous', ['delta' => ($pct > 0 ? '+' : '').$pct.'%']))
                    ->descriptionIcon($pct > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($pct > 0 ? 'success' : 'danger');
            }
        }

        return $stat->description(__('widgets.'.$key.'_desc'))->color('gray');
    }
}
