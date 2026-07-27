<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Error pages (404/500/…) render through the exception handler, which bypasses
 * the SetLocale middleware, and on an unmatched URL never starts a session. The
 * plaintext `locale` cookie is the only signal available there, so it must
 * drive both the translated copy and the <html dir> for RTL.
 */
class ErrorPageLocaleTest extends TestCase
{
    public function test_unmatched_404_uses_the_locale_cookie(): void
    {
        $response = $this->withUnencryptedCookie('locale', 'ar')
            ->get('/no-such-page-'.uniqid());

        $response->assertNotFound();
        $response->assertSee(__('errors.404_headline', [], 'ar'));
        $response->assertSee('dir="rtl"', false);
    }

    public function test_unmatched_404_defaults_to_english_without_a_cookie(): void
    {
        $response = $this->get('/no-such-page-'.uniqid());

        $response->assertNotFound();
        $response->assertSee(__('errors.404_headline', [], 'en'));
        $response->assertSee('dir="ltr"', false);
    }

    public function test_an_unsupported_cookie_value_falls_back_to_english(): void
    {
        $response = $this->withUnencryptedCookie('locale', 'xx')
            ->get('/no-such-page-'.uniqid());

        $response->assertNotFound();
        $response->assertSee(__('errors.404_headline', [], 'en'));
    }
}
