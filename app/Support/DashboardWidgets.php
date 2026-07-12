<?php

declare(strict_types=1);

namespace App\Support;

use App\Filament\App\Widgets\CompetitorBenchmarkWidget;
use App\Filament\App\Widgets\CompetitorGrowthChart;
use App\Filament\App\Widgets\LatestReviews;
use App\Filament\App\Widgets\ListingPerformanceChart;
use App\Filament\App\Widgets\ListingPerformanceOverview;
use App\Filament\App\Widgets\ListingSearchesWidget;
use App\Filament\App\Widgets\RatingTrendChart;
use App\Filament\App\Widgets\ReviewStatsOverview;
use App\Filament\App\Widgets\StarDistributionChart;

/**
 * Registry of the dashboard widgets a user can show/hide via the "Customize"
 * action. The selection is stored per user (users.dashboard_widgets, central);
 * null means "everything visible" — so widgets added later default to visible
 * only for users who never customized.
 */
class DashboardWidgets
{
    /** Widget key => lang key suffix (pages/dashboard.widget_*). */
    public const KEYS = [
        'review_stats',
        'star_distribution',
        'rating_trend',
        'performance',
        'performance_chart',
        'searches',
        'latest_reviews',
        'competitors',
        'competitors_chart',
    ];

    /** @return array<string, string> key => translated label */
    public static function labels(): array
    {
        return collect(self::KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => __('pages/dashboard.widget_'.$key)])
            ->all();
    }

    /** Whether the signed-in user has this widget enabled. */
    public static function visible(string $key): bool
    {
        $enabled = auth()->user()?->dashboard_widgets;

        if (! is_array($enabled)) {
            return true; // never customized → everything on
        }

        return in_array($key, $enabled, true);
    }

    /** The user's current selection, for prefilling the customize form. */
    public static function enabled(): array
    {
        $enabled = auth()->user()?->dashboard_widgets;

        return is_array($enabled) ? array_values(array_intersect(self::KEYS, $enabled)) : self::KEYS;
    }

    /**
     * ORDER key => widget class, in the default grid order (mirrors the
     * classes' $sort values). Order keys are per WIDGET, so widgets sharing a
     * visibility key (the two competitor widgets) still reorder independently.
     *
     * @return array<string, class-string>
     */
    public static function classes(): array
    {
        return [
            'review_stats' => ReviewStatsOverview::class,
            'star_distribution' => StarDistributionChart::class,
            'rating_trend' => RatingTrendChart::class,
            'latest_reviews' => LatestReviews::class,
            'performance' => ListingPerformanceOverview::class,
            'performance_chart' => ListingPerformanceChart::class,
            'searches' => ListingSearchesWidget::class,
            'competitors' => CompetitorBenchmarkWidget::class,
            'competitors_chart' => CompetitorGrowthChart::class,
        ];
    }

    /**
     * The signed-in user's full widget order; defaults appended for widgets
     * the stored preference doesn't know about yet.
     *
     * @return list<string>
     */
    public static function order(): array
    {
        $defaults = array_keys(self::classes());
        $saved = auth()->user()?->dashboard_widget_order;

        if (! is_array($saved)) {
            return $defaults;
        }

        $saved = array_values(array_intersect($saved, $defaults));

        return [...$saved, ...array_values(array_diff($defaults, $saved))];
    }

    /** Position of a widget class in the user's order (unknown classes go last). */
    public static function position(string $class): int
    {
        $key = array_search($class, self::classes(), true);
        $index = $key === false ? false : array_search($key, self::order(), true);

        return $index === false ? PHP_INT_MAX : $index;
    }

    /** The user's width override for an order key ('full' or 1), null = widget default. */
    public static function spanOverride(string $key): int|string|null
    {
        $spans = auth()->user()?->dashboard_widget_spans;
        $value = is_array($spans) ? ($spans[$key] ?? null) : null;

        return in_array($value, ['full', 1], true) ? $value : null;
    }

    /** The user's width override for a widget class. */
    public static function spanOverrideForClass(string $class): int|string|null
    {
        $key = array_search($class, self::classes(), true);

        return $key === false ? null : self::spanOverride($key);
    }
}
