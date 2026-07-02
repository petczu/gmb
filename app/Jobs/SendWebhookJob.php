<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Delivers a single webhook with an HMAC-SHA256 signature, retrying with
 * backoff. The delivery + endpoint rows are tenant-scoped, so we re-initialize
 * the workspace's tenancy before touching them.
 */
class SendWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 1800, 7200];

    public function __construct(public string $workspaceId, public int $deliveryId) {}

    public function handle(): void
    {
        $this->initTenant();

        $delivery = WebhookDelivery::with('endpoint')->find($this->deliveryId);

        if ($delivery === null || $delivery->endpoint === null) {
            return;
        }

        $endpoint = $delivery->endpoint;
        $body = $delivery->payload;
        $signature = hash_hmac('sha256', $body, $endpoint->secret);

        $delivery->forceFill([
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
        ])->save();

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Id' => (string) $delivery->id,
                'X-Webhook-Signature' => 'sha256='.$signature,
            ])
            ->withBody($body, 'application/json')
            ->post($endpoint->url);

        $delivery->forceFill([
            'response_status' => $response->status(),
            'response_body' => Str::limit($response->body(), 2000),
        ])->save();

        if ($response->successful()) {
            $delivery->forceFill(['status' => WebhookDelivery::STATUS_SUCCESS])->save();

            return;
        }

        // Non-2xx → throw so the queue retries with backoff. failed() marks the
        // final failure once retries are exhausted.
        throw new \RuntimeException("Webhook endpoint returned HTTP {$response->status()}");
    }

    public function failed(?Throwable $e): void
    {
        $this->initTenant();

        WebhookDelivery::query()
            ->where('id', $this->deliveryId)
            ->update(['status' => WebhookDelivery::STATUS_FAILED]);
    }

    private function initTenant(): void
    {
        $current = tenancy()->initialized ? tenant()?->getTenantKey() : null;

        if ((string) $current === $this->workspaceId) {
            return;
        }

        $workspace = Workspace::find($this->workspaceId);

        if ($workspace !== null) {
            tenancy()->initialize($workspace);
        }
    }
}
