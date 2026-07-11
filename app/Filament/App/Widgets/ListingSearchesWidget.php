<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Services\Listings\ListingPerformance;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * Two-column widget under the performance chart: where the profile views come
 * from (Search/Maps × desktop/mobile) and the top Google search queries that
 * surfaced the profile (monthly data, last 3 months).
 */
class ListingSearchesWidget extends Widget
{
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected string $view = 'filament.app.widgets.listing-searches';

    /** Sized lazy-loading skeleton (the data comes from an external API). */
    protected ?string $placeholderHeight = '20rem';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return DashboardWidgets::visible('searches')
            && ListingPerformanceOverview::dataAvailable();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        if (DemoDashboard::active()) {
            $demo = DemoDashboard::performance();

            return [
                'views' => $demo['views'],
                'breakdown' => $this->breakdown($demo['totals'], $demo['views']),
                'keywords' => DemoDashboard::searchKeywords(),
            ];
        }

        $period = DashboardPeriod::fromFilters($this->pageFilters);
        $performance = app(ListingPerformance::class);

        $metrics = $performance->metrics($period->locationIds, $period->start, $period->end);

        return [
            'views' => $metrics['views'],
            'breakdown' => $this->breakdown($metrics['totals'], $metrics['views']),
            'keywords' => $performance->keywords($period->locationIds),
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @return list<array{label: string, value: int, pct: float, color: string}>
     */
    private function breakdown(array $totals, int $views): array
    {
        $rows = [
            ['key' => 'search_desktop', 'label' => __('widgets.perf_search_desktop'), 'color' => '#2563eb'],
            ['key' => 'search_mobile', 'label' => __('widgets.perf_search_mobile'), 'color' => '#ef4444'],
            ['key' => 'maps_desktop', 'label' => __('widgets.perf_maps_desktop'), 'color' => '#f59e0b'],
            ['key' => 'maps_mobile', 'label' => __('widgets.perf_maps_mobile'), 'color' => '#22c55e'],
        ];

        return array_map(fn (array $row): array => [
            'label' => $row['label'],
            'value' => $totals[$row['key']] ?? 0,
            'pct' => $views > 0 ? round(($totals[$row['key']] ?? 0) / $views * 100, 1) : 0.0,
            'color' => $row['color'],
        ], $rows);
    }
}
