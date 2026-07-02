<?php

declare(strict_types=1);

namespace App\Api;

/**
 * The scopes a workspace API key may grant. Mirrors the MCP tool surface:
 * read locations/reviews/analytics, plus publishing replies. Each REST route is
 * gated by one of these via the `ability` middleware.
 */
class ApiAbilities
{
    public const REVIEWS_READ = 'reviews:read';

    public const REVIEWS_REPLY = 'reviews:reply';

    public const LOCATIONS_READ = 'locations:read';

    public const ANALYTICS_READ = 'analytics:read';

    /**
     * All grantable abilities, in display order.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::REVIEWS_READ,
            self::LOCATIONS_READ,
            self::ANALYTICS_READ,
            self::REVIEWS_REPLY,
        ];
    }

    /**
     * Translated label/help for the settings UI.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $ability) {
            $options[$ability] = __('pages/api_keys.scope_'.str_replace([':'], '_', $ability));
        }

        return $options;
    }

    public static function isValid(string $ability): bool
    {
        return in_array($ability, self::all(), true);
    }
}
