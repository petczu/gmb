<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Webhooks\WebhookEvents;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Webhook endpoints/deliveries are tenant models on the default connection —
 * built here on sqlite. The delivery job is exercised with an empty workspaceId
 * so it skips tenancy re-initialization and reads straight from the test DB.
 */
class WebhookDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('webhook_endpoints', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('active')->default(true);
            $table->dateTime('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('webhook_endpoint_id');
            $table->string('event');
            $table->longText('payload');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        parent::tearDown();
    }

    private function endpoint(array $overrides = []): WebhookEndpoint
    {
        return WebhookEndpoint::create(array_merge([
            'url' => 'https://example.test/hook',
            'secret' => 'whsec_test_secret',
            'events' => [WebhookEvents::REVIEW_CREATED],
            'active' => true,
        ], $overrides));
    }

    public function test_secret_is_generated_when_missing(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.test/hook',
            'events' => [WebhookEvents::REVIEW_CREATED],
        ]);

        $this->assertStringStartsWith('whsec_', $endpoint->secret);
    }

    public function test_subscribes_to_respects_active_and_events(): void
    {
        $endpoint = $this->endpoint(['events' => [WebhookEvents::GOAL_REACHED]]);

        $this->assertTrue($endpoint->subscribesTo(WebhookEvents::GOAL_REACHED));
        $this->assertFalse($endpoint->subscribesTo(WebhookEvents::REVIEW_CREATED));

        $endpoint->update(['active' => false]);
        $this->assertFalse($endpoint->fresh()->subscribesTo(WebhookEvents::GOAL_REACHED));
    }

    public function test_job_posts_signed_payload_and_marks_success(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $endpoint = $this->endpoint();
        $body = '{"event":"review.created","data":{"id":7}}';
        $delivery = $endpoint->deliveries()->create([
            'event' => WebhookEvents::REVIEW_CREATED,
            'payload' => $body,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        (new SendWebhookJob('', $delivery->id))->handle();

        $expected = 'sha256='.hash_hmac('sha256', $body, $endpoint->secret);
        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/hook'
            && $request->header('X-Webhook-Signature')[0] === $expected
            && $request->header('X-Webhook-Event')[0] === WebhookEvents::REVIEW_CREATED
            && $request->body() === $body);

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
        $this->assertSame(200, $delivery->response_status);
        $this->assertSame(1, $delivery->attempts);
    }

    public function test_job_records_failure_and_throws_for_retry(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $endpoint = $this->endpoint();
        $delivery = $endpoint->deliveries()->create([
            'event' => WebhookEvents::REVIEW_CREATED,
            'payload' => '{"x":1}',
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        try {
            (new SendWebhookJob('', $delivery->id))->handle();
            $this->fail('Expected the job to throw on a non-2xx response.');
        } catch (\RuntimeException $e) {
            // expected — the queue retries with backoff
        }

        $delivery->refresh();
        $this->assertSame(500, $delivery->response_status);
        $this->assertSame(1, $delivery->attempts);
    }

    public function test_events_catalogue(): void
    {
        $this->assertTrue(WebhookEvents::isValid('goal.reached'));
        $this->assertFalse(WebhookEvents::isValid('goal.missed'));
        $this->assertCount(4, WebhookEvents::all());
    }
}
