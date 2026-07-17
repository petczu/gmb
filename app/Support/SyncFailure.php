<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * Maps a raw review-sync failure (usually a Guzzle HTTP exception from the
 * provider) to a short, human-readable reason for the Locations table tooltip.
 * The raw message is still stored in last_sync_error and logged for debugging.
 */
class SyncFailure
{
    public static function humanize(Throwable|string $error): string
    {
        $raw = strtolower($error instanceof Throwable ? $error->getMessage() : $error);

        return (string) match (true) {
            str_contains($raw, 'timed out'), str_contains($raw, 'timeout'), str_contains($raw, 'connection') => __('resources/locations.sync_err_timeout'),
            str_contains($raw, '429'), str_contains($raw, 'rate limit'), str_contains($raw, 'quota') => __('resources/locations.sync_err_rate'),
            str_contains($raw, '401'), str_contains($raw, '403'), str_contains($raw, 'unauthorized'), str_contains($raw, 'forbidden'), str_contains($raw, 'token') => __('resources/locations.sync_err_auth'),
            str_contains($raw, '404'), str_contains($raw, 'not found') => __('resources/locations.sync_err_notfound'),
            str_contains($raw, '500'), str_contains($raw, '502'), str_contains($raw, '503'), str_contains($raw, 'server error') => __('resources/locations.sync_err_server'),
            default => __('resources/locations.sync_err_generic'),
        };
    }

    /** Expected/transient errors we don't page Sentry about (e.g. a freshly
     *  connected location Zernio is still backfilling). */
    public static function isTransient(Throwable|string $error): bool
    {
        $raw = strtolower($error instanceof Throwable ? $error->getMessage() : $error);

        return str_contains($raw, '404') || str_contains($raw, 'not found');
    }
}
