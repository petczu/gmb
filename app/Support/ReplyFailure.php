<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * Maps a raw reply-publish failure (usually a Guzzle HTTP exception from the
 * provider) to a short, human-readable, localized reason for display on the
 * Approvals page. The raw message is kept in the logs for debugging.
 */
class ReplyFailure
{
    public static function humanize(Throwable|string $error): string
    {
        $raw = strtolower($error instanceof Throwable ? $error->getMessage() : $error);

        return (string) match (true) {
            str_contains($raw, '404'), str_contains($raw, 'not found') => __('resources/auto_reply.error_not_found'),
            str_contains($raw, '429'), str_contains($raw, 'rate limit'), str_contains($raw, 'quota') => __('resources/auto_reply.error_rate_limited'),
            str_contains($raw, '401'), str_contains($raw, '403'), str_contains($raw, 'unauthorized'), str_contains($raw, 'forbidden'), str_contains($raw, 'permission') => __('resources/auto_reply.error_unauthorized'),
            default => __('resources/auto_reply.error_generic'),
        };
    }

    /**
     * Is a stored failure worth retrying automatically? Transient reasons
     * (generic "try again later" / rate limiting) are; a missing review/location
     * or an authorization problem is structural, so retrying just fails again.
     * An unknown/empty reason is treated as retryable.
     */
    public static function isRetryable(?string $storedError): bool
    {
        $error = trim((string) $storedError);
        if ($error === '') {
            return true;
        }

        foreach (['error_not_found', 'error_unauthorized'] as $key) {
            foreach (['en', 'de'] as $locale) {
                if ($error === trim((string) __('resources/auto_reply.'.$key, [], $locale))) {
                    return false;
                }
            }
        }

        return true;
    }
}
