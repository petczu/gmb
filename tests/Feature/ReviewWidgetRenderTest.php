<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReviewWidget;
use Tests\TestCase;

/**
 * DB-free contract tests for the public embed renderer. The central ReviewWidget
 * row is exercised in-memory so the suite needs no tenancy. Assertions target
 * rendered CONTENT (names, texts) rather than class names, which also appear in
 * the always-present style block.
 */
class ReviewWidgetRenderTest extends TestCase
{
    private function widget(array $overrides = []): ReviewWidget
    {
        $widget = new ReviewWidget([
            'token' => 'abctoken123',
            'name' => 'Demo',
            'settings' => array_merge(ReviewWidget::defaultSettings(), $overrides),
        ]);

        $widget->snapshot = [
            'summary' => ['average' => 4.8, 'count' => 210],
            'reviews' => [
                ['id' => 1, 'author' => 'Anna B.', 'rating' => 5, 'text' => 'Loved it, great place!', 'reply' => 'Thanks Anna!', 'location' => 'Downtown', 'date' => '10 Oct 2025', 'date_iso' => '2025-10-10T00:00:00+00:00', 'link' => null],
                ['id' => 2, 'author' => 'John', 'rating' => 4, 'text' => 'Solid experience.', 'reply' => null, 'location' => 'Downtown', 'date' => '02 Oct 2025', 'date_iso' => '2025-10-02T00:00:00+00:00', 'link' => null],
            ],
        ];

        return $widget;
    }

    private function render(ReviewWidget $widget): string
    {
        return view('widgets.embed', ['widget' => $widget, 'jsonLd' => []])->render();
    }

    public function test_it_renders_every_layout_with_review_content(): void
    {
        foreach (['slider', 'grid', 'list', 'masonry'] as $layout) {
            $html = $this->render($this->widget(['layout' => $layout]));

            $this->assertStringContainsString('rw-w-abctoken123', $html);
            $this->assertStringContainsString('Anna B.', $html);
            $this->assertStringContainsString('Loved it, great place!', $html);
        }
    }

    public function test_slider_layout_emits_track_markup(): void
    {
        $slider = $this->render($this->widget(['layout' => 'slider']));
        $grid = $this->render($this->widget(['layout' => 'grid']));

        // The <div class="rw-track"> element only exists in the slider markup.
        $this->assertStringContainsString('<div class="rw-track">', $slider);
        $this->assertStringNotContainsString('<div class="rw-track">', $grid);
    }

    public function test_header_title_and_summary_render(): void
    {
        $html = $this->render($this->widget(['header_title' => 'Acme Reviews', 'show_summary' => true]));

        $this->assertStringContainsString('Acme Reviews', $html);
        $this->assertStringContainsString('<b>4.8</b>', $html);

        $hidden = $this->render($this->widget(['show_header' => false]));
        $this->assertStringNotContainsString('Acme Reviews', $hidden);
    }

    public function test_owner_reply_only_shows_when_enabled(): void
    {
        $off = $this->render($this->widget(['show_reply' => false]));
        $this->assertStringNotContainsString('Thanks Anna!', $off);

        $on = $this->render($this->widget(['show_reply' => true]));
        $this->assertStringContainsString('Thanks Anna!', $on);
    }

    public function test_branding_link_toggles(): void
    {
        $on = $this->render($this->widget(['branding' => true]));
        $this->assertStringContainsString('rel="noopener"', $on);

        $off = $this->render($this->widget(['branding' => false]));
        $this->assertStringNotContainsString('rel="noopener"', $off);
    }

    public function test_embed_snippet_contains_loader_and_mount(): void
    {
        $snippet = $this->widget()->embedSnippet();

        $this->assertStringContainsString('w/abctoken123.js', $snippet);
        $this->assertStringContainsString('id="reviews-widget-abctoken123"', $snippet);
    }
}
