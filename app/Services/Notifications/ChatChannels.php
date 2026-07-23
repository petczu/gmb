<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Workspace;
use App\Support\Locales;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Slack / Telegram alert channels for a workspace. Config lives on the
 * workspace (virtual `chat_channels` attribute): slack_webhook_url,
 * telegram_bot_token, telegram_chat_id, language and subscribed categories.
 * Sends are best-effort — a chat outage must never break review sync.
 */
class ChatChannels
{
    /**
     * @return array{slack_enabled: bool, slack_webhook_url: ?string, telegram_enabled: bool, telegram_bot_token: ?string, telegram_chat_id: ?string, language: string, categories: list<string>}
     */
    public static function config(Workspace $workspace): array
    {
        $raw = (array) ($workspace->getAttribute('chat_channels') ?? []);

        return [
            // Per-channel switches; configs saved before the toggles existed
            // count as enabled when their credentials are filled.
            'slack_enabled' => (bool) ($raw['slack_enabled'] ?? filled($raw['slack_webhook_url'] ?? null)),
            'slack_webhook_url' => $raw['slack_webhook_url'] ?? null,
            'telegram_enabled' => (bool) ($raw['telegram_enabled'] ?? filled($raw['telegram_bot_token'] ?? null)),
            'telegram_bot_token' => $raw['telegram_bot_token'] ?? null,
            'telegram_chat_id' => $raw['telegram_chat_id'] ?? null,
            'language' => in_array($raw['language'] ?? null, Locales::codes(), true) ? $raw['language'] : 'en',
            'categories' => array_values($raw['categories'] ?? [NotificationCategory::REPUTATION]),
        ];
    }

    public static function enabled(Workspace $workspace): bool
    {
        $config = self::config($workspace);

        return self::slackReady($config) || self::telegramReady($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function slackReady(array $config): bool
    {
        return $config['slack_enabled'] && filled($config['slack_webhook_url']);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function telegramReady(array $config): bool
    {
        return $config['telegram_enabled']
            && filled($config['telegram_bot_token'])
            && filled($config['telegram_chat_id']);
    }

    /**
     * Send one message to every configured channel if the category is
     * subscribed. $message is a lang key under chat.*; $params interpolate.
     *
     * @param  array<string, mixed>  $params
     */
    public static function send(Workspace $workspace, string $category, string $message, array $params = []): void
    {
        $config = self::config($workspace);

        if (! in_array($category, $config['categories'], true)) {
            return;
        }

        $text = __('chat.'.$message, $params, $config['language']);

        self::deliver($workspace, $config, $text);
    }

    /** Send a test message to all configured channels, ignoring category filters. */
    public static function sendTest(Workspace $workspace): void
    {
        $config = self::config($workspace);

        self::deliver($workspace, $config, __('chat.test', ['workspace' => $workspace->name], $config['language']));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function deliver(Workspace $workspace, array $config, string $text): void
    {
        if (self::slackReady($config)) {
            try {
                Http::timeout(5)->connectTimeout(3)
                    ->post((string) $config['slack_webhook_url'], ['text' => $text])
                    ->throw();
            } catch (Throwable $e) {
                Log::warning('Slack notification failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);
            }
        }

        if (self::telegramReady($config)) {
            try {
                Http::timeout(5)->connectTimeout(3)
                    ->post(sprintf('https://api.telegram.org/bot%s/sendMessage', $config['telegram_bot_token']), [
                        'chat_id' => $config['telegram_chat_id'],
                        'text' => $text,
                    ])
                    ->throw();
            } catch (Throwable $e) {
                Log::warning('Telegram notification failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
