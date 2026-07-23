<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\Templates\EmailTemplateCatalog;
use App\Mail\Templates\EmailTemplateRenderer;
use Tests\TestCase;

class EmailTemplateRendererTest extends TestCase
{
    private function renderer(): EmailTemplateRenderer
    {
        return app(EmailTemplateRenderer::class);
    }

    public function test_substitutes_scalar_placeholders(): void
    {
        $html = $this->renderer()->preview('Hi :name, welcome.', ['name' => 'Peter']);

        $this->assertStringContainsString('Peter', $html);
        $this->assertStringNotContainsString(':name', $html);
    }

    public function test_renders_a_branded_cta_button(): void
    {
        $html = $this->renderer()->preview('{{ button:Open Repunio }}', ['url' => 'https://example.test/go']);

        $this->assertStringContainsString('https://example.test/go', $html);
        $this->assertStringContainsString('Open Repunio', $html);
        $this->assertStringContainsString('#1800ff', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_hidden_buttons_leave_no_cta_or_token_behind(): void
    {
        // Guest recipients (no login): the CTA is dropped entirely.
        $html = $this->renderer()->preview(
            "Intro line.\n\n{{ button:Open Repunio }}\n\nOutro line.",
            ['url' => 'https://example.test/go'],
            hideButtons: true,
        );

        $this->assertStringContainsString('Intro line.', $html);
        $this->assertStringContainsString('Outro line.', $html);
        $this->assertStringNotContainsString('Open Repunio', $html);
        $this->assertStringNotContainsString('https://example.test/go', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_injects_named_blocks(): void
    {
        $html = $this->renderer()->preview('Before {{ table }} after', [], ['table' => '<div id="injected">ROWS</div>']);

        // The CSS inliner may add a style attribute, so assert on the marker + content.
        $this->assertStringContainsString('id="injected"', $html);
        $this->assertStringContainsString('ROWS', $html);
        $this->assertStringNotContainsString('{{ table }}', $html);
    }

    public function test_wraps_body_in_the_brand_layout(): void
    {
        $html = $this->renderer()->preview('Just a line.', []);

        // The branded wrapper pulls in the logo image header.
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('Just a line.', $html);
    }

    public function test_every_catalogue_template_has_defaults_in_every_locale(): void
    {
        foreach (EmailTemplateCatalog::keys() as $key) {
            foreach (EmailTemplateCatalog::locales() as $locale) {
                $this->assertNotSame('', EmailTemplateCatalog::defaultSubject($key, $locale), "$key/$locale subject");
                $this->assertNotSame('', EmailTemplateCatalog::defaultBody($key, $locale), "$key/$locale body");
            }
        }
    }
}
