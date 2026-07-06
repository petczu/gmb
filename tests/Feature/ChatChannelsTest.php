<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Workspace;
use App\Services\Notifications\ChatChannels;
use App\Services\Notifications\NotificationCategory;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Slack / Telegram alert channels: config parsing, category gating and the
 * outgoing HTTP payloads. The workspace is a Mockery partial so no tenant DB
 * is touched (mirrors DripSeriesTest).
 */
class ChatChannelsTest extends TestCase
{
    /**
     * @param  array<string, mixed>|null  $chatChannels
     */
    protected function workspace(?array $chatChannels): Workspace
    {
        $workspace = Mockery::mock(Workspace::class)->makePartial();
        $workspace->shouldReceive('getAttribute')->with('chat_channels')->andReturn($chatChannels);
        $workspace->shouldReceive('getAttribute')->with('name')->andReturn('Acme');
        $workspace->shouldReceive('getAttribute')->with('id')->andReturn('ws-1');

        return $workspace;
    }

    public function test_sends_to_slack_and_telegram_when_category_subscribed(): void
    {
        Http::fake();

        ChatChannels::send($this->workspace([
            'slack_webhook_url' => 'https://hooks.slack.test/services/abc',
            'telegram_bot_token' => 'bot-token',
            'telegram_chat_id' => '12345',
            'language' => 'en',
            'categories' => [NotificationCategory::REPUTATION],
        ]), NotificationCategory::REPUTATION, 'new_reviews', [
            'count' => 2, 'location' => 'Downtown Cafe', 'url' => 'https://app.test/reviews',
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://hooks.slack.test/services/abc'
            && str_contains((string) $request['text'], '2 new review(s) for Downtown Cafe'));

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.telegram.org/botbot-token/sendMessage')
            && $request['chat_id'] === '12345');
    }

    public function test_skips_unsubscribed_categories(): void
    {
        Http::fake();

        ChatChannels::send($this->workspace([
            'slack_webhook_url' => 'https://hooks.slack.test/services/abc',
            'categories' => [NotificationCategory::REPUTATION],
        ]), NotificationCategory::BILLING, 'goal_reached', ['goal' => 10, 'actual' => 12]);

        Http::assertNothingSent();
    }

    public function test_messages_use_the_configured_language(): void
    {
        Http::fake();

        ChatChannels::send($this->workspace([
            'slack_webhook_url' => 'https://hooks.slack.test/services/abc',
            'language' => 'de',
            'categories' => [NotificationCategory::REVIEW_GROWTH],
        ]), NotificationCategory::REVIEW_GROWTH, 'goal_reached', ['goal' => 10, 'actual' => 12]);

        Http::assertSent(fn ($request): bool => str_contains((string) $request['text'], 'Bewertungsziel erreicht'));
    }

    public function test_enabled_requires_a_complete_channel(): void
    {
        $this->assertFalse(ChatChannels::enabled($this->workspace(null)));
        $this->assertFalse(ChatChannels::enabled($this->workspace(['telegram_bot_token' => 'x'])));
        $this->assertTrue(ChatChannels::enabled($this->workspace(['slack_webhook_url' => 'https://hooks.slack.test/x'])));
        $this->assertTrue(ChatChannels::enabled($this->workspace([
            'telegram_bot_token' => 'x', 'telegram_chat_id' => '1',
        ])));
    }

    public function test_disabled_channel_is_skipped_even_with_credentials(): void
    {
        Http::fake();

        ChatChannels::send($this->workspace([
            'slack_enabled' => false,
            'slack_webhook_url' => 'https://hooks.slack.test/services/abc',
            'categories' => [NotificationCategory::REPUTATION],
        ]), NotificationCategory::REPUTATION, 'new_reviews', ['count' => 1, 'location' => 'X', 'url' => '']);

        Http::assertNothingSent();
    }

    public function test_a_failing_channel_does_not_throw(): void
    {
        Http::fake(['hooks.slack.test/*' => Http::response('no_service', 404)]);

        ChatChannels::send($this->workspace([
            'slack_webhook_url' => 'https://hooks.slack.test/services/broken',
            'categories' => [NotificationCategory::REPUTATION],
        ]), NotificationCategory::REPUTATION, 'new_reviews', ['count' => 1, 'location' => 'X', 'url' => '']);

        $this->assertTrue(true); // reaching here means the failure was swallowed
    }
}
