<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Curated business categories a workspace can pick on the Company page. Each key
 * maps to a set of review-collection tips (the review_tips lang files) used by
 * the coaching emails.
 */
class BusinessCategories
{
    /** @var list<string> */
    public const KEYS = [
        'food_drink', 'retail', 'health', 'beauty_wellness', 'fitness',
        'hospitality', 'entertainment', 'professional', 'home_services',
        'automotive', 'education', 'real_estate', 'other',
    ];

    /** @return array<string, string> value => localized label */
    public static function options(): array
    {
        $options = [];
        foreach (self::KEYS as $key) {
            $options[$key] = __('categories.'.$key);
        }

        return $options;
    }

    public static function has(?string $key): bool
    {
        return $key !== null && in_array($key, self::KEYS, true);
    }
}
