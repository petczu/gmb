<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\CompetitorBattle;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Review growth as separate lines: the own side ("You") plus one line per
 * tracked competitor across ALL battles, rebased to 0 at the period start so
 * profiles of very different sizes compare on one scale. Chart.js legend
 * clicks toggle individual lines.
 */
class CompetitorGrowthChart extends ChartWidget
{
    use Concerns\HasSkeletonPlaceholder;
    use Concerns\HasUserGridSpan;
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 9;

    /** Sized lazy-loading skeleton (the data comes from an external API). */
    protected ?string $placeholderHeight = '19rem';

    protected int|string|array $columnSpan = 'full';

    /** Distinct competitor line colours (the own line is always brand-primary). */
    private const PALETTE = ['#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#0ea5e9', '#ec4899', '#84cc16', '#64748b'];

    public function getHeading(): ?string
    {
        return __('widgets.competitor_chart_title');
    }

    public function getDescription(): ?string
    {
        return __('widgets.competitor_chart_desc');
    }

    public static function canView(): bool
    {
        return tenancy()->initialized
            && ! DemoDashboard::active()
            && DashboardWidgets::visible('competitors_chart')
            && (auth()->user()?->can('view_competitors') ?? false)
            && app(PlacesClient::class)->configured()
            && CompetitorBattle::query()->exists();
    }

    protected function getData(): array
    {
        $battles = $this->battles();

        if ($battles->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        // ALL competitors across the battles as individual lines (the legend
        // toggles them); "You" is the union of every battle's own side.
        $ownLocationIds = $battles
            ->flatMap(fn (CompetitorBattle $b): array => $b->ownLocationIds())
            ->unique()
            ->values()
            ->all();

        $names = $battles
            ->flatMap(fn (CompetitorBattle $b) => $b->competitors)
            ->filter(fn ($c): bool => filled($c->place_id))
            ->mapWithKeys(fn ($c): array => [(string) $c->place_id => (string) $c->name])
            ->all();

        $period = DashboardPeriod::fromFilters($this->pageFilters);

        $series = app(CompetitorTrends::class)->growthSeries(
            array_keys($names),
            $ownLocationIds,
            $period->start,
            $period->end,
        );

        $datasets = [[
            'label' => __('widgets.competitor_chart_you'),
            'data' => $series['own'],
            'borderColor' => '#2d19ec',
            'backgroundColor' => 'rgba(45, 25, 236, 0.08)',
            'borderWidth' => 3,
            'pointRadius' => 0,
            'tension' => 0.3,
            'fill' => true,
        ]];

        $i = 0;
        foreach ($series['places'] as $placeId => $values) {
            $color = self::PALETTE[$i % count(self::PALETTE)];
            $datasets[] = [
                'label' => Str::limit((string) ($names[$placeId] ?? $placeId), 28),
                'data' => $values,
                'borderColor' => $color,
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'pointRadius' => 0,
                'tension' => 0.3,
                'spanGaps' => true,
            ];
            $i++;
        }

        return ['datasets' => $datasets, 'labels' => $series['labels']];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            // The legend is the line toggle — keep it visible.
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxHeight' => 6]]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }

    /** @return Collection<int, CompetitorBattle> battles honouring the dashboard location filter */
    private function battles(): Collection
    {
        $battles = CompetitorBattle::query()->with('competitors')->latest('created_at')->get();

        $period = DashboardPeriod::fromFilters($this->pageFilters);
        if ($period->locationIds !== []) {
            $filtered = $battles->filter(
                fn (CompetitorBattle $b): bool => array_intersect($b->ownLocationIds(), $period->locationIds) !== [],
            );
            $battles = $filtered->isNotEmpty() ? $filtered : $battles;
        }

        return $battles->values();
    }
}
