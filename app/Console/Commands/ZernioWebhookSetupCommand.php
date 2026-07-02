<?php

declare(strict_types=1);

namespace App\Console\Commands;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use Zernio\Api\WebhooksApi;
use Zernio\Configuration;
use Zernio\Model\CreateWebhookSettingsRequest;
use Zernio\Model\UpdateWebhookSettingsRequest;

/**
 * Registers (or updates) the single platform-wide Zernio webhook pointing at
 * route('zernio.webhook'). Idempotent: reads the current settings first and
 * updates the existing webhook if one is present, otherwise creates a new one.
 *
 * Configured exactly like ZernioProvider: the shared platform key
 * (services.reviews.zernio_key) as the bearer token, with the optional
 * ZERNIO_BASE_URL host (a trailing /v1 stripped — SDK paths already carry it).
 */
class ZernioWebhookSetupCommand extends Command
{
    protected $signature = 'zernio:webhook-setup';

    protected $description = 'Register/update the Zernio webhook for review + account events';

    /** Events we ingest (no auto-reply triggering — ingest only). */
    private const EVENTS = [
        'review.new',
        'review.updated',
        'account.connected',
        'account.disconnected',
    ];

    public function handle(): int
    {
        $secret = (string) config('services.reviews.webhook_secret');
        if ($secret === '') {
            $this->error('ZERNIO_WEBHOOK_SECRET is not set — configure it before registering the webhook.');

            return self::FAILURE;
        }

        $url = rtrim((string) config('app.url'), '/').'/'.ltrim(
            (string) parse_url(route('zernio.webhook'), PHP_URL_PATH),
            '/'
        );

        $api = $this->makeApi();

        $existing = $api->getWebhookSettings();
        $current = collect($existing->getWebhooks() ?? [])->first();

        if ($current !== null) {
            $request = new UpdateWebhookSettingsRequest([
                '_id' => $current->getId(),
                'name' => 'Repunio',
                'url' => $url,
                'secret' => $secret,
                'events' => self::EVENTS,
                'is_active' => true,
            ]);

            $response = $api->updateWebhookSettings($request);
            $this->info('Updated Zernio webhook.');
        } else {
            $request = new CreateWebhookSettingsRequest([
                'name' => 'Repunio',
                'url' => $url,
                'secret' => $secret,
                'events' => self::EVENTS,
                'is_active' => true,
            ]);

            $response = $api->createWebhookSettings($request);
            $this->info('Created Zernio webhook.');
        }

        $webhook = $response->getWebhook();
        if ($webhook !== null) {
            $this->line('  id:  '.$webhook->getId());
            $this->line('  url: '.$webhook->getUrl());
        }

        return self::SUCCESS;
    }

    private function makeApi(): WebhooksApi
    {
        $config = Configuration::getDefaultConfiguration()
            ->setAccessToken((string) config('services.reviews.zernio_key'));

        if ($base = config('services.reviews.zernio_base_url')) {
            $config->setHost(rtrim((string) preg_replace('#/v1/?$#', '', (string) $base), '/'));
        }

        $http = new GuzzleClient(['timeout' => 30, 'connect_timeout' => 5]);

        return new WebhooksApi($http, $config);
    }
}
