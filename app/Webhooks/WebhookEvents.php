<?php

declare(strict_types=1);

namespace App\Webhooks;

/**
 * The outbound webhook event catalogue. An endpoint subscribes to any subset of
 * these; the dispatcher fans a payload out to every active endpoint listening
 * for the fired event.
 */
class WebhookEvents
{
    public const REVIEW_CREATED = 'review.created';

    public const REPLY_PUBLISHED = 'reply.published';

    public const GOAL_REACHED = 'goal.reached';

    public const ANOMALY_DETECTED = 'anomaly.detected';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::REVIEW_CREATED,
            self::REPLY_PUBLISHED,
            self::GOAL_REACHED,
            self::ANOMALY_DETECTED,
        ];
    }

    /**
     * Translated label for the settings UI, keyed by event name.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $event) {
            $options[$event] = __('pages/webhooks.event_'.str_replace('.', '_', $event));
        }

        return $options;
    }

    public static function isValid(string $event): bool
    {
        return in_array($event, self::all(), true);
    }
}
