<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Services\Posts\PostPublisher;
use App\Services\Zernio\ZernioRestClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Google posts via Zernio's NATIVE POST /v1/posts (Bearer + the same API key
 * as review sync). Each target location becomes one platforms[] entry with
 * the account id + GBP location id the location was connected with.
 */
class GooglePostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reviews.zernio_base_url', 'https://zernio.test/api/v1');
        config()->set('services.reviews.zernio_key', 'test-key');

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function ($table): void {
            $table->increments('id');
            $table->string('type', 20);
            $table->text('caption')->nullable();
            $table->string('title')->nullable();
            $table->string('cta_type', 20)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('photo_category', 30)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('voucher_code')->nullable();
            $table->string('redeem_url', 2048)->nullable();
            $table->string('terms_url', 2048)->nullable();
            $table->json('location_ids');
            $table->json('source_ids');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20);
            $table->json('external_ids')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    private function location(string $externalId = '1185302053868319269', string $accountId = 'acc-1'): Location
    {
        return Location::create([
            'external_id' => $externalId,
            'zernio_account_id' => $accountId,
            'name' => 'Downtown Cafe',
        ]);
    }

    public function test_publish_now_sends_native_payload_and_stores_result(): void
    {
        Http::fake([
            'zernio.test/api/v1/posts' => Http::response([
                '_id' => 'zp-1',
                'status' => 'published',
                'platforms' => [['platform' => 'googlebusiness', 'status' => 'published']],
            ], 201),
        ]);

        $location = $this->location();
        $post = Post::create([
            'type' => 'update',
            'caption' => 'New summer menu!',
            'cta_type' => 'learn_more',
            'cta_url' => 'https://example.com/menu',
            'image_url' => 'https://example.com/img.jpg',
            'location_ids' => [$location->id],
            'source_ids' => [$location->external_id],
            'status' => 'in_progress',
        ]);

        app(PostPublisher::class)->publish($post, collect([$location]));

        Http::assertSent(function ($request): bool {
            $platform = $request['platforms'][0];

            return str_ends_with($request->url(), '/posts')
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['content'] === 'New summer menu!'
                && $request['publishNow'] === true
                && $request['mediaItems'][0] === ['type' => 'image', 'url' => 'https://example.com/img.jpg']
                && $platform['platform'] === 'googlebusiness'
                && $platform['accountId'] === 'acc-1'
                && $platform['platformSpecificData']['locationId'] === '1185302053868319269'
                && $platform['platformSpecificData']['topicType'] === 'STANDARD'
                && $platform['platformSpecificData']['callToAction'] === ['type' => 'LEARN_MORE', 'url' => 'https://example.com/menu'];
        });

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertSame(['zp-1'], $post->external_ids);
    }

    public function test_scheduled_offer_payload_includes_event_and_offer_objects(): void
    {
        $location = $this->location();

        $post = new Post([
            'type' => 'offer',
            'caption' => '20% off',
            'title' => 'Summer sale',
            'voucher_code' => 'SUN20',
            'redeem_url' => 'https://example.com/redeem',
        ]);
        $post->starts_at = CarbonImmutable::parse('2026-08-01 09:00:00');
        $post->ends_at = CarbonImmutable::parse('2026-08-15 18:00:00');
        $post->scheduled_at = CarbonImmutable::parse('2026-07-30 08:30:00');

        $payload = app(PostPublisher::class)->payload($post, collect([$location]));

        $this->assertSame('2026-07-30T08:30:00+00:00', $payload['scheduledFor']);
        $this->assertArrayNotHasKey('publishNow', $payload);

        $data = $payload['platforms'][0]['platformSpecificData'];
        $this->assertSame('OFFER', $data['topicType']);
        $this->assertSame('Summer sale', $data['event']['title']);
        $this->assertSame(['year' => 2026, 'month' => 8, 'day' => 1], $data['event']['schedule']['startDate']);
        $this->assertSame(['hours' => 18, 'minutes' => 0], $data['event']['schedule']['endTime']);
        $this->assertSame('SUN20', $data['offer']['couponCode']);
        $this->assertSame('https://example.com/redeem', $data['offer']['redeemOnlineUrl']);
        $this->assertArrayNotHasKey('callToAction', $data);
    }

    public function test_multi_location_targets_one_platform_entry_per_location(): void
    {
        $one = $this->location('111');
        $two = $this->location('222');

        $post = new Post(['type' => 'update', 'caption' => 'Hi']);

        $payload = app(PostPublisher::class)->payload($post, collect([$one, $two]));

        $this->assertCount(2, $payload['platforms']);
        $this->assertSame('111', $payload['platforms'][0]['platformSpecificData']['locationId']);
        $this->assertSame('222', $payload['platforms'][1]['platformSpecificData']['locationId']);
    }

    public function test_publish_failure_stores_error(): void
    {
        Http::fake([
            'zernio.test/*' => Http::response(['error' => 'Account not connected'], 422),
        ]);

        $location = $this->location();
        $post = Post::create([
            'type' => 'update',
            'caption' => 'Hello',
            'location_ids' => [$location->id],
            'source_ids' => [$location->external_id],
            'status' => 'in_progress',
        ]);

        app(PostPublisher::class)->publish($post, collect([$location]));

        $post->refresh();
        $this->assertSame('failed', $post->status);
        $this->assertStringContainsString('Account not connected', (string) $post->error);
    }

    public function test_partial_platform_failure_is_surfaced(): void
    {
        Http::fake([
            'zernio.test/*' => Http::response([
                '_id' => 'zp-2',
                'status' => 'published',
                'platforms' => [
                    ['platform' => 'googlebusiness', 'status' => 'published'],
                    ['platform' => 'googlebusiness', 'status' => 'failed', 'error' => 'Location suspended'],
                ],
            ], 201),
        ]);

        $location = $this->location();
        $post = Post::create([
            'type' => 'update',
            'caption' => 'Hello',
            'location_ids' => [$location->id],
            'source_ids' => [$location->external_id],
            'status' => 'in_progress',
        ]);

        app(PostPublisher::class)->publish($post, collect([$location]));

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertSame('Location suspended', $post->error);
    }

    public function test_client_reports_unconfigured_without_api_key(): void
    {
        config()->set('services.reviews.zernio_key', null);

        $this->assertFalse(app(ZernioRestClient::class)->configured());
    }
}
