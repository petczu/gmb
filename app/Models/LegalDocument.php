<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — an editable, versioned legal document (currently only the
 * Terms of Service). One row per key+locale; `version` is shared across a
 * key's locales and only moves on an explicit "publish new version", which is
 * what forces users whose accepted version is older to re-accept on login.
 */
class LegalDocument extends Model
{
    /** Central table — must not follow the tenant connection switch. */
    protected $connection = 'mysql';

    public const TERMS = 'terms';

    protected $fillable = ['key', 'locale', 'body', 'version'];

    /** The currently published version of a document (0 = none published). */
    public static function currentVersion(string $key): int
    {
        return (int) static::query()->where('key', $key)->max('version');
    }

    /** The document body for a locale, falling back to English. */
    public static function bodyFor(string $key, string $locale): ?string
    {
        $row = static::query()->where('key', $key)->where('locale', $locale)->first()
            ?? static::query()->where('key', $key)->where('locale', 'en')->first();

        return $row?->body;
    }
}
