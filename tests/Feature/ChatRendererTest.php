<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\ChatRenderer;
use Tests\TestCase;

/**
 * Ask-AI message rendering: markdown (tables, bold), inline chart blocks, and
 * HTML safety (raw tags in the model output must be stripped).
 */
class ChatRendererTest extends TestCase
{
    public function test_markdown_table_becomes_html_table(): void
    {
        $html = ChatRenderer::render(<<<'MD'
        Here is the breakdown:

        | Location | Rating |
        |---|---|
        | Dubai | 5.0 |
        | Vienna | 4.9 |
        MD)->toHtml();

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Location</th>', $html);
        $this->assertStringContainsString('<td>Dubai</td>', $html);
    }

    public function test_bold_and_lists_render(): void
    {
        $html = ChatRenderer::render("**210 reviews** this month\n\n- one\n- two")->toHtml();

        $this->assertStringContainsString('<strong>210 reviews</strong>', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
    }

    public function test_bar_chart_block_renders_svg_free_bars(): void
    {
        $content = "Star split:\n\n```chart\n{\"type\":\"bar\",\"title\":\"Stars\",\"data\":[{\"label\":\"5★\",\"value\":202},{\"label\":\"1★\",\"value\":1}]}\n```";

        $html = ChatRenderer::render($content)->toHtml();

        $this->assertStringContainsString('Stars', $html);
        $this->assertStringContainsString('202', $html);
        // The JSON must be consumed, not printed as a code block.
        $this->assertStringNotContainsString('```chart', $html);
        $this->assertStringNotContainsString('"type"', $html);
    }

    public function test_donut_chart_renders_svg(): void
    {
        $content = "```chart\n{\"type\":\"donut\",\"title\":\"Mix\",\"data\":[{\"label\":\"Search\",\"value\":60},{\"label\":\"Maps\",\"value\":40}]}\n```";

        $html = ChatRenderer::render($content)->toHtml();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('Search', $html);
        $this->assertStringContainsString('60%', $html);
    }

    public function test_raw_html_is_stripped(): void
    {
        $html = ChatRenderer::render('Hello <script>alert(1)</script> **world**')->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>world</strong>', $html);
    }

    public function test_malformed_chart_block_is_left_as_text(): void
    {
        $html = ChatRenderer::render("```chart\nnot json\n```")->toHtml();

        // Falls through to a normal (escaped) code block rather than crashing.
        $this->assertStringContainsString('not json', $html);
    }
}
