<?php

declare(strict_types=1);

namespace App\Support;

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
}
