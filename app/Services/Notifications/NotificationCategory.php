<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * The buckets that every workspace-bound notification falls into. Recipients are
 * configured per category on the Notifications settings page; individual mail
 * types map to one of these.
 */
final class NotificationCategory
{
    public const REVIEW_GROWTH = 'review_growth';

    public const REPUTATION = 'reputation';

    public const OPERATIONS = 'operations';

    public const BILLING = 'billing';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::REVIEW_GROWTH,
            self::REPUTATION,
            self::OPERATIONS,
            self::BILLING,
        ];
    }
}
