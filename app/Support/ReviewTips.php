<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Picks review-collection tips for a coaching email: category-specific tips
 * first, then universal ones, rotated by an offset (the ISO week) so the
 * selection varies week to week instead of repeating the same three.
 */
class ReviewTips
{
    /**
     * @return list<string>
     */
    public static function pick(?string $category, int $count = 3, int $offset = 0, string $locale = 'en'): array
    {
        $specific = BusinessCategories::has($category) ? (array) __('review_tips.'.$category, [], $locale) : [];
        $universal = (array) __('review_tips.universal', [], $locale);

        $pool = array_values(array_unique([...array_values($specific), ...array_values($universal)]));
        $total = count($pool);

        if ($total === 0) {
            return [];
        }

        $out = [];
        for ($i = 0; $i < min($count, $total); $i++) {
            $out[] = (string) $pool[($offset + $i) % $total];
        }

        return $out;
    }
}
