<?php

declare(strict_types=1);

namespace App\Support;

/**
 * URLs on the marketing site (repunio.com) — the canonical home of the legal
 * pages. The app only keeps the Terms ACCEPTANCE flows (registration scroll
 * box, re-accept interstitial), which render the admin-managed DB copy.
 */
class MarketingSite
{
    public static function url(string $path = ''): string
    {
        $base = rtrim((string) config('services.marketing.url', 'https://repunio.com'), '/');

        return $path === '' ? $base : $base.'/'.ltrim($path, '/');
    }

    /** Locale-aware URL of a legal page (terms | privacy | cookies). */
    public static function legal(string $page): string
    {
        $paths = app()->getLocale() === 'de'
            ? ['terms' => 'de/nutzungsbedingungen', 'privacy' => 'de/datenschutz', 'cookies' => 'de/cookies']
            : ['terms' => 'terms', 'privacy' => 'privacy', 'cookies' => 'cookies'];

        return self::url($paths[$page] ?? $page);
    }
}
