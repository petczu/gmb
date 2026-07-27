<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Throwable;

/**
 * The single source of truth for the languages the app ships in: UI, emails,
 * reports and public review pages all read from here instead of hard-coding
 * ['en', 'de']. Names are in the language itself (that's what a speaker looks
 * for in a picker); `rtl` flags right-to-left scripts so the layout can flip.
 */
class Locales
{
    public const DEFAULT = 'en';

    /**
     * Shipped locales, in menu order. Keys are Laravel locale codes and must
     * match a directory under lang/.
     *
     * @var array<string, array{name: string, rtl?: bool}>
     */
    public const ALL = [
        'en' => ['name' => 'English'],
        'de' => ['name' => 'Deutsch'],
        'es' => ['name' => 'Español'],
        'fr' => ['name' => 'Français'],
        'it' => ['name' => 'Italiano'],
        'nl' => ['name' => 'Nederlands'],
        'pt_BR' => ['name' => 'Português (Brasil)'],
        'pl' => ['name' => 'Polski'],
        'ja' => ['name' => '日本語'],
        'tr' => ['name' => 'Türkçe'],
        'ar' => ['name' => 'العربية', 'rtl' => true],
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    /**
     * code => native name, for select fields.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (array $meta): string => $meta['name'], self::ALL);
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::ALL);
    }

    /** The given locale when shipped, otherwise the default. */
    public static function normalize(?string $locale): string
    {
        return self::isSupported($locale) ? (string) $locale : self::DEFAULT;
    }

    public static function isRtl(?string $locale): bool
    {
        return (bool) (self::ALL[self::normalize($locale)]['rtl'] ?? false);
    }

    /** 'rtl' or 'ltr', for the <html dir> attribute. */
    public static function direction(?string $locale): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    public static function name(?string $locale): string
    {
        return self::ALL[self::normalize($locale)]['name'];
    }

    /**
     * Best locale for the current request, resolved without the SetLocale
     * middleware. Error pages (404/500/…) render through the exception handler,
     * which bypasses route middleware, so app()->getLocale() would otherwise be
     * the default. A signed-in user's stored choice wins, then the guest's
     * session choice. Never throws when there is no started session (an
     * unmatched-route 404), falling back to the current app locale.
     */
    public static function forRequest(?Request $request = null): string
    {
        try {
            $request ??= request();

            if (self::isSupported($userLocale = $request?->user()?->locale)) {
                return (string) $userLocale;
            }

            if ($request?->hasSession() && self::isSupported($sessionLocale = $request->session()->get('locale'))) {
                return (string) $sessionLocale;
            }

            // Plaintext cookie: the only signal available on an unmatched-route
            // 404, where neither auth nor session middleware ran. Set by
            // SetLocale and the language switcher.
            if (is_string($cookieLocale = $request?->cookie('locale')) && self::isSupported($cookieLocale)) {
                return $cookieLocale;
            }
        } catch (Throwable) {
            // No auth/session context available: fall through to the app locale.
        }

        return app()->getLocale();
    }

    /**
     * Resolve and apply the request locale. Safe to call from anywhere,
     * including error views that render outside the locale middleware.
     */
    public static function applyForRequest(?Request $request = null): string
    {
        app()->setLocale($locale = self::forRequest($request));

        return $locale;
    }
}
