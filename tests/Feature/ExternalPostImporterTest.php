<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Services\Posts\ExternalPostImporter;
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
            $table->json('location_ids')->nullable();
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

        $this->assertSame(['locations' => 1, 'imported' => 1], $result);

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

        $this->assertSame(['locations' => 0, 'imported' => 0], $result);
        $this->assertSame(0, Post::count());
    }
}
