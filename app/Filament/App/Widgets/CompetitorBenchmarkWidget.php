<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Competitor;
use App\Models\CompetitorBattle;
use App\Models\Location;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use App\Support\DemoDashboard;
use App\Support\Sparkline;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/**
 * Dashboard summary of the competitor battles: per battle the weighted
 * "you vs them" rating, new reviews in the dashboard's period on both sides,
 * and a sparkline of the competitors' review growth. Shows a set-up invite
 * while no battles exist yet.
 */
class CompetitorBenchmarkWidget extends Widget
{
    use Concerns\HasSkeletonPlaceholder;
    use Concerns\HasUserGridSpan;
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 8;

    protected string $view = 'filament.app.widgets.competitor-benchmark';

    /** Sized lazy-loading skeleton (the data comes from an external API). */
    protected string $skeletonVariant = 'table';

    protected ?string $placeholderHeight = '14rem';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return tenancy()->initialized
            && ! DemoDashboard::active()
            && DashboardWidgets::visible('competitors')
            && (auth()->user()?->can('view_competitors') ?? false)
            && app(PlacesClient::class)->configured();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $period = DashboardPeriod::fromFilters($this->pageFilters);

        $battles = CompetitorBattle::query()
            ->with('competitors')
            ->latest('created_at')
            ->get();

        // Honour the dashboard's location filter: with a location selected, show
        // only competitors compared against it (same city). "All locations"
        // (empty filter) shows every competitor.
        if ($period->locationIds !== []) {
            $battles = $battles->filter(
                fn (CompetitorBattle $b): bool => array_intersect($b->ownLocationIds(), $period->locationIds) !== [],
            );
        }

        $trends = app(CompetitorTrends::class);

        $rows = $battles->map(function (CompetitorBattle $battle) use ($trends, $period): array {
            $summary = $trends->placesSummary(
                $battle->competitors->pluck('place_id')->filter()->values()->all(),
                $period->start,
                $period->end,
            );

            $ownRating = CompetitorTrends::weightedRating(
                $battle->ownLocations()->map(fn (Location $l): array => [
                    'rating' => $l->rating !== null ? (float) $l->rating : null,
                    'reviews_count' => (int) $l->reviews_count,
                ]),
            );

            $theirRating = CompetitorTrends::weightedRating(
                $battle->competitors->map(fn (Competitor $c): array => [
                    'rating' => $c->rating !== null ? (float) $c->rating : null,
                    'reviews_count' => (int) $c->reviews_count,
                ]),
            );

            return [
                'name' => $battle->displayName(),
                'ownRating' => $ownRating,
                'theirRating' => $theirRating,
                'delta' => $ownRating !== null && $theirRating !== null ? round($ownRating - $theirRating, 1) : null,
                'ownNew' => $trends->ownNewReviewsForMany($battle->ownLocationIds(), $period->start, $period->end),
                'theirNew' => $summary['reviews_delta'],
                'spark' => Sparkline::svg($summary['spark'], 120, 28),
            ];
        })->values()->all();

        return [
            'rows' => $rows,
        ];
    }
}
