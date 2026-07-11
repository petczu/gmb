<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Location;
use App\Models\Review;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class StarDistributionChart extends ChartWidget
{
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected ?string $heading = 'Star distribution';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return tenancy()->initialized && DashboardWidgets::visible('star_distribution');
    }

    protected function getData(): array
    {
        // No location yet → demo data behind the connect-first overlay.
        if (DemoDashboard::active()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Reviews',
                        'data' => DemoDashboard::starCounts(),
                        'backgroundColor' => ['#16a34a', '#65a30d', '#ca8a04', '#ea580c', '#dc2626'],
                    ],
                ],
                'labels' => ['5★', '4★', '3★', '2★', '1★'],
            ];
        }

        $period = DashboardPeriod::fromFilters($this->pageFilters);

        $counts = Review::query()
            ->when($period->locationIds !== [], fn (Builder $q): Builder => $q->whereIn('location_id', $period->locationIds))
            ->whereBetween('created_at_external', [$period->start, $period->end])
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $data = [];
        for ($star = 5; $star >= 1; $star--) {
            $data[] = (int) ($counts[$star] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Reviews',
                    'data' => $data,
                    'backgroundColor' => ['#16a34a', '#65a30d', '#ca8a04', '#ea580c', '#dc2626'],
                ],
            ],
            'labels' => ['5★', '4★', '3★', '2★', '1★'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
