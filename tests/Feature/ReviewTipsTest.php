<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\BusinessCategories;
use App\Support\ReviewTips;
use Tests\TestCase;

class ReviewTipsTest extends TestCase
{
    public function test_returns_the_requested_number_of_tips(): void
    {
        $this->assertCount(3, ReviewTips::pick('food_drink', 3, 0, 'en'));
        $this->assertCount(1, ReviewTips::pick('food_drink', 1, 0, 'en'));
    }

    public function test_category_specific_tips_come_first(): void
    {
        $foodTips = (array) __('review_tips.food_drink', [], 'en');
        $first = ReviewTips::pick('food_drink', 1, 0, 'en')[0];

        $this->assertContains($first, $foodTips);
    }

    public function test_rotates_with_the_offset(): void
    {
        $this->assertNotSame(
            ReviewTips::pick('food_drink', 3, 0, 'en'),
            ReviewTips::pick('food_drink', 3, 1, 'en'),
        );
    }

    public function test_unknown_category_falls_back_to_universal(): void
    {
        $universal = (array) __('review_tips.universal', [], 'en');
        $tips = ReviewTips::pick('not_a_category', 2, 0, 'en');

        $this->assertNotEmpty($tips);
        foreach ($tips as $tip) {
            $this->assertContains($tip, $universal);
        }
    }

    public function test_every_category_has_tips_in_both_locales(): void
    {
        foreach (BusinessCategories::KEYS as $key) {
            foreach (['en', 'de'] as $locale) {
                $this->assertNotEmpty((array) __('review_tips.'.$key, [], $locale), "$key/$locale");
            }
        }
    }
}
