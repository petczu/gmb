<?php

declare(strict_types=1);

namespace App\Support;

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
}
