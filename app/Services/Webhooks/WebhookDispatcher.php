<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

/**
 * Fans an event out to every active tenant endpoint subscribed to it. Must be
 * called inside an initialized tenancy (mirrors NotificationDispatcher). Each
 * matching endpoint gets a pending WebhookDelivery row + a queued send job.
 */
class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function dispatch(string $event, array $data): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        $workspace = tenant();

        $endpoints = WebhookEndpoint::query()
            ->where('active', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint): bool => $endpoint->subscribesTo($event));

        if ($endpoints->isEmpty()) {
            return;
        }

        $body = (string) json_encode([
            'event' => $event,
            'workspace' => ['id' => $workspace->getKey(), 'name' => $workspace->name],
            'occurred_at' => now()->toIso8601String(),
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($endpoints as $endpoint) {
            $delivery = $endpoint->deliveries()->create([
                'event' => $event,
                'payload' => $body,
                'status' => WebhookDelivery::STATUS_PENDING,
            ]);

            $endpoint->forceFill(['last_triggered_at' => now()])->saveQuietly();

            SendWebhookJob::dispatch((string) $workspace->getKey(), $delivery->id);
        }
    }
}
