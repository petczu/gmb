<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Locales;
use Tests\TestCase;

/**
 * The connection indicator is a self-contained Blade view injected panel-wide
 * via a render hook. Guard that it compiles and resolves its copy in every
 * shipped locale so a missing key never ships to the panel.
 */
class ConnectionStatusViewTest extends TestCase
{
    public function test_it_renders_translated_copy_in_every_shipped_locale(): void
    {
        $keys = ['connection.offline_title', 'connection.offline_body', 'connection.online_title', 'connection.online_body'];

        foreach (Locales::codes() as $locale) {
            $this->app->setLocale($locale);
            $html = view('filament.connection-status')->render();

            foreach ($keys as $key) {
                $translated = __($key, [], $locale);

                $this->assertNotSame($key, $translated, "Missing translation [{$key}] for locale [{$locale}].");
                $this->assertStringContainsString($translated, $html);
            }
        }
    }
}
