<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\CompetitorBattle;
use App\Models\Location;
use App\Models\LocationGroup;
use App\Services\Competitors\CompetitorGeo;
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

    /** Competitor line colours: warm/neutral only, so they never blend into the
     *  cool "you" lines (which are also dashed). */
    private const PALETTE = ['#f59e0b', '#10b981', '#ef4444', '#ec4899', '#84cc16', '#64748b', '#b45309', '#be185d', '#059669', '#a16207'];

    /** Own-side line colours: distinct cool hues, drawn dashed to stand apart. */
    private const YOU_PALETTE = ['#2d19ec', '#06b6d4', '#7c3aed', '#0d9488', '#4338ca'];

    /** Growth (rebased to 0) vs Total (absolute counts). Default: growth. */
    public ?string $filter = 'growth';

    public function getHeading(): ?string
    {
        return __('widgets.competitor_chart_title');
    }

    public function getDescription(): ?string
    {
        return $this->filter === 'total'
            ? __('widgets.competitor_chart_desc_total')
            : __('widgets.competitor_chart_desc');
    }

    protected function getFilters(): ?array
    {
        return [
            'growth' => __('widgets.competitor_chart_mode_growth'),
            'total' => __('widgets.competitor_chart_mode_total'),
        ];
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

        // Respect the dashboard location filter for the "You" line: battles are
        // auto-scoped to all locations, so without this it would always count
        // every location even when one is selected.
        if ($period->locationIds !== []) {
            $ownLocationIds = array_values(array_intersect($ownLocationIds, $period->locationIds));
        }

        $mode = $this->filter === 'total' ? 'total' : 'growth';
        $trends = app(CompetitorTrends::class);

        // Competitor lines; the own side is computed separately, one line per
        // location, so a single-location dashboard filter isolates that location
        // instead of summing every own location into one "You" line.
        $series = $trends->growthSeries(array_keys($names), [], $period->start, $period->end, $mode);

        // Own side: locations that belong to a location group sum into one line
        // named after the group; anything left ungrouped draws its own line
        // (mirrors the competitor side, where a named battle sums its members).
        $locationNames = Location::query()->whereIn('id', $ownLocationIds)->orderBy('name')->pluck('name', 'id');

        // Build the own lines: [label, member location ids].
        $ownLines = [];
        $grouped = [];
        foreach (LocationGroup::query()->orderBy('name')->get() as $group) {
            $memberIds = array_values(array_intersect($group->locationIds(), $ownLocationIds));
            if ($memberIds === []) {
                continue;
            }
            $ownLines[] = ['label' => (string) $group->name, 'ids' => $memberIds];
            $grouped = array_merge($grouped, $memberIds);
        }
        foreach ($locationNames as $locationId => $locationName) {
            if (in_array((int) $locationId, $grouped, true)) {
                continue;
            }
            $ownLines[] = ['label' => (string) $locationName, 'ids' => [(int) $locationId]];
        }

        $singleOwn = count($ownLines) === 1;
        $datasets = [];
        $y = 0;
        foreach ($ownLines as $line) {
            $datasets[] = $this->ownDataset(
                $line['label'],
                $trends->growthSeries([], $line['ids'], $period->start, $period->end, $mode)['own'],
                $y++,
                $singleOwn,
            );
        }

        // Competitor side: a named battle is a group — its member places sum
        // into one line; an unnamed battle draws one line per competitor place.
        $i = 0;
        foreach ($battles as $battle) {
            $placeIds = $battle->competitors
                ->pluck('place_id')
                ->filter()
                ->map(fn ($p): string => (string) $p)
                ->values();
            if ($placeIds->isEmpty()) {
                continue;
            }

            if (filled($battle->name)) {
                $datasets[] = $this->competitorDataset(
                    Str::limit($battle->displayName(), 28),
                    $this->sumSeries($placeIds->map(fn (string $pid): array => $series['places'][$pid] ?? [])->all()),
                    $i++,
                );

                continue;
            }

            foreach ($placeIds as $placeId) {
                $datasets[] = $this->competitorDataset(
                    Str::limit((string) ($names[$placeId] ?? $placeId), 28),
                    $series['places'][$placeId] ?? [],
                    $i++,
                );
            }
        }

        return ['datasets' => $datasets, 'labels' => $series['labels']];
    }

    /**
     * @param  array<int, int|null>  $data
     * @return array<string, mixed>
     */
    private function ownDataset(string $label, array $data, int $index, bool $fill): array
    {
        return [
            'label' => Str::limit($label, 28),
            'data' => $data,
            'borderColor' => self::YOU_PALETTE[$index % count(self::YOU_PALETTE)],
            'backgroundColor' => $fill ? 'rgba(45, 25, 236, 0.08)' : 'transparent',
            'borderWidth' => 3,
            // Dashed so every "you" line reads as ours at a glance, distinct
            // from the solid competitor lines regardless of colour overlap.
            'borderDash' => [6, 4],
            'pointRadius' => 0,
            'tension' => 0.3,
            'fill' => $fill,
        ];
    }

    /**
     * @param  array<int, int|null>  $data
     * @return array<string, mixed>
     */
    private function competitorDataset(string $label, array $data, int $index): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => self::PALETTE[$index % count(self::PALETTE)],
            'backgroundColor' => 'transparent',
            'borderWidth' => 2,
            'pointRadius' => 0,
            'tension' => 0.3,
            'spanGaps' => true,
        ];
    }

    /**
     * Element-wise sum of several place series; an index is null only when every
     * member is null there (so a genuine gap stays a gap, not a false zero).
     *
     * @param  array<int, array<int, int|null>>  $seriesList
     * @return array<int, int|null>
     */
    private function sumSeries(array $seriesList): array
    {
        $length = 0;
        foreach ($seriesList as $series) {
            $length = max($length, count($series));
        }

        $out = [];
        for ($idx = 0; $idx < $length; $idx++) {
            $sum = null;
            foreach ($seriesList as $series) {
                $value = $series[$idx] ?? null;
                if ($value !== null) {
                    $sum = ($sum ?? 0) + $value;
                }
            }
            $out[] = $sum;
        }

        return $out;
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
            // Total mode: don't force zero — so hiding a large competitor via
            // the legend lets the axis rescale to the remaining lines.
            'scales' => ['y' => ['beginAtZero' => $this->filter !== 'total', 'ticks' => ['precision' => 0]]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }

    /** @return Collection<int, CompetitorBattle> battles honouring the dashboard location filter */
    private function battles(): Collection
    {
        $battles = CompetitorBattle::query()->with('competitors')->latest('created_at')->get();

        // With a location selected, keep only competitors in its city — matched
        // by each competitor's own coordinates so a grouped battle's multi-city
        // own_location_ids don't leak in competitors from other cities.
        $period = DashboardPeriod::fromFilters($this->pageFilters);
        if ($period->locationIds !== []) {
            $selected = Location::query()->whereIn('id', $period->locationIds)->get(['latitude', 'longitude']);

            $battles = $battles->filter(fn (CompetitorBattle $b): bool => CompetitorGeo::anyCompetitorInSelected($b->competitors, $selected)
                ?? (array_intersect($b->ownLocationIds(), $period->locationIds) !== []));
        }

        return $battles->values();
    }
}
