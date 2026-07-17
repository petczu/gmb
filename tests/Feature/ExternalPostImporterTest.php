<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Services\Posts\ExternalPostImporter;
use App\Services\Zernio\ZernioRestClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Importing previously-published Google posts (Zernio external posts) into the
 * calendar as read-only published Posts, deduped on the platform-native id.
 * Tenant tables live on the default sqlite connection; Zernio HTTP is faked.
 */
class ExternalPostImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.reviews.zernio_key' => 'test-key',
            'services.reviews.zernio_base_url' => 'https://zernio.test/api/v1',
        ]);

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('zernio_account_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('cid')->nullable();
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
            $table->json('location_ids');
            $table->json('source_ids');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('origin', 20)->default('app');
            $table->string('platform_post_id')->nullable();
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

    private function fakeZernio(): void
    {
        Http::fake([
            '*/posts/sync-external' => Http::response(['synced' => ['postsSynced' => 1]]),
            '*/posts*' => Http::response([
                'posts' => [[
                    '_id' => 'zernio-1',
                    'origin' => 'external',
                    'content' => 'Weekend special!',
                    'mediaItems' => [['type' => 'image', 'url' => 'https://cdn.test/a.jpg']],
                    'scheduledFor' => '2026-06-01T09:00:00Z',
                    'status' => 'published',
                    'platforms' => [[
                        'platform' => 'googlebusiness',
                        'status' => 'published',
                        'publishedAt' => '2026-06-01T09:00:00Z',
                        'platformPostId' => 'g-123',
                        'platformPostUrl' => 'https://maps.google.com/post/g-123',
                    ]],
                ]],
                'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'pages' => 1],
            ]),
        ]);
    }

    public function test_it_imports_an_external_post_as_a_published_post(): void
    {
        Location::create(['name' => 'Marina', 'zernio_account_id' => 'acc-1']);
        $this->fakeZernio();

        $result = app(ExternalPostImporter::class)->import();

        $this->assertSame(['locations' => 1, 'imported' => 1, 'seen' => 1], $result);

        $post = Post::sole();
        $this->assertSame('imported', $post->origin);
        $this->assertSame('published', $post->status);
        $this->assertSame('Weekend special!', $post->caption);
        $this->assertSame('g-123', $post->platform_post_id);
        $this->assertSame('https://cdn.test/a.jpg', $post->image_url);
        $this->assertSame([1], $post->location_ids);
    }

    public function test_it_is_idempotent_on_the_platform_post_id(): void
    {
        Location::create(['name' => 'Marina', 'zernio_account_id' => 'acc-1']);
        $this->fakeZernio();

        app(ExternalPostImporter::class)->import();
        $second = app(ExternalPostImporter::class)->import();

        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, Post::count());
    }

    public function test_it_attributes_shared_account_posts_by_cid(): void
    {
        // Two locations under ONE Zernio account; the feed carries both, each
        // post's CID (in its url) decides which location it belongs to.
        Location::create(['name' => 'City Walk', 'zernio_account_id' => 'acc-1', 'cid' => '111']);
        $riyadh = Location::create(['name' => 'Riyadh', 'zernio_account_id' => 'acc-1', 'cid' => '222']);

        Http::fake([
            '*/posts/sync-external' => Http::response(['synced' => []]),
            '*/posts*' => Http::response([
                'posts' => [[
                    '_id' => 'z-1', 'content' => 'Riyadh room',
                    'mediaItems' => [], 'scheduledFor' => '2026-06-01T09:00:00Z', 'status' => 'published',
                    'platforms' => [['platformPostId' => 'g-r', 'platformPostUrl' => 'https://local.google.com/place?id=222&use=posts']],
                ]],
                'pagination' => ['page' => 1, 'pages' => 1],
            ]),
        ]);

        app(ExternalPostImporter::class)->import();

        $post = Post::sole();
        $this->assertSame([$riyadh->id], $post->location_ids);
        $this->assertSame('Riyadh room', $post->caption);
    }

    public function test_store_creates_an_imported_post_from_normalized_data(): void
    {
        // The post.external.created webhook stores through this same public
        // contract, so lock the mapping + idempotency here.
        $location = Location::create(['name' => 'Marina', 'zernio_account_id' => 'acc-1']);
        $importer = app(ExternalPostImporter::class);

        $stored = $importer->store($location, [
            'platform_post_id' => 'g-web-1',
            'content' => 'From webhook',
            'image_url' => 'https://cdn.test/x.jpg',
            'url' => 'https://maps.google.com/post/g-web-1',
            'published_at' => '2026-05-01T10:00:00Z',
        ]);

        $this->assertTrue($stored);
        $post = Post::sole();
        $this->assertSame('imported', $post->origin);
        $this->assertSame('published', $post->status);
        $this->assertSame('From webhook', $post->caption);
        $this->assertSame('g-web-1', $post->platform_post_id);

        // A repeat delivery (e.g. post.external.updated) is a no-op.
        $this->assertFalse($importer->store($location, ['platform_post_id' => 'g-web-1']));
        $this->assertSame(1, Post::count());
    }

    public function test_it_skips_locations_without_a_connected_account(): void
    {
        Location::create(['name' => 'Not connected', 'zernio_account_id' => null]);
        Http::fake();

        $result = app(ExternalPostImporter::class)->import();

        $this->assertSame(['locations' => 0, 'imported' => 0, 'seen' => 0], $result);
        $this->assertSame(0, Post::count());
    }

    public function test_snapshot_selects_and_syncs_each_location_on_the_account(): void
    {
        // Two locations under ONE account: the snapshot must switch the selected
        // location for each before syncing, so both get covered.
        Location::create(['name' => 'City Walk', 'zernio_account_id' => 'acc-1', 'external_id' => 'loc-a', 'cid' => '111']);
        Location::create(['name' => 'Riyadh', 'zernio_account_id' => 'acc-1', 'external_id' => 'loc-b', 'cid' => '222']);

        Http::fake([
            '*/accounts/*/gmb-locations' => Http::response([]),
            '*/posts/sync-external' => Http::response(['synced' => []]),
            '*/posts*' => Http::response([
                'posts' => [[
                    '_id' => 'z-1', 'content' => 'A post',
                    'mediaItems' => [], 'scheduledFor' => '2026-06-01T09:00:00Z', 'status' => 'published',
                    'platforms' => [['platformPostId' => 'g-1', 'platformPostUrl' => 'https://local.google.com/place?id=111&use=posts']],
                ]],
                'pagination' => ['page' => 1, 'pages' => 1],
            ]),
        ]);

        // Subclass to skip the real 15s debounce sleep.
        $importer = new class(app(ZernioRestClient::class)) extends ExternalPostImporter
        {
            protected function pause(int $seconds): void {}
        };

        $result = $importer->snapshot();

        $this->assertSame(2, $result['locations']);

        // The selected location was switched to each location's external id.
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/accounts/acc-1/gmb-locations')
            && ($request['selectedLocationId'] ?? null) === 'loc-a');
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/accounts/acc-1/gmb-locations')
            && ($request['selectedLocationId'] ?? null) === 'loc-b');

        // The post is stored once (deduped on platform id across both passes).
        $this->assertSame(1, Post::count());
    }
}
