<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityEntry;
use App\Models\Location;
use App\Models\Review;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Activity feed: logger writes, tenancy guard, and the reply.published hook on
 * the Review model. Tables live on the default sqlite connection and tenancy
 * is flagged initialized directly (no stancl bootstrap), like McpToolsTest.
 */
class ActivityLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('activity_log', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action', 60);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('reply_text')->nullable();
            $table->string('reply_status')->nullable();
            $table->string('reply_source')->nullable();
            $table->dateTime('created_at_external')->nullable();
            $table->timestamps();
        });

        // Publishing a reply retires open queue items (Review model hook).
        Schema::create('auto_reply_queue', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->string('status');
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });

        // Empty endpoints table so the reply.published webhook dispatch no-ops.
        Schema::create('webhook_endpoints', function ($table): void {
            $table->increments('id');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        tenancy()->initialized = true;
    }

    protected function tearDown(): void
    {
        tenancy()->initialized = false;
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('activity_log');
        parent::tearDown();
    }

    public function test_log_writes_an_entry_with_meta_and_subject(): void
    {
        $location = Location::create(['name' => 'Downtown Cafe']);

        ActivityLogger::log('location.connected', ['location' => 'Downtown Cafe'], $location);

        $entry = ActivityEntry::sole();
        $this->assertSame('location.connected', $entry->action);
        $this->assertSame(['location' => 'Downtown Cafe'], $entry->meta);
        $this->assertSame('Location', $entry->subject_type);
        $this->assertSame($location->id, $entry->subject_id);
        $this->assertNull($entry->user_id);
    }

    public function test_log_is_a_noop_outside_tenancy(): void
    {
        tenancy()->initialized = false;

        ActivityLogger::log('apikey.created', ['name' => 'CI']);

        $this->assertSame(0, ActivityEntry::count());
    }

    public function test_publishing_a_reply_logs_reply_published(): void
    {
        Location::create(['name' => 'Downtown Cafe']);
        $review = Review::create([
            'location_id' => 1,
            'author_name' => 'Ann',
            'rating' => 5,
            'created_at_external' => '2026-06-20 10:00:00',
        ]);

        $review->update(['reply_text' => 'Thanks!', 'reply_status' => 'published', 'reply_source' => 'manual']);

        $entry = ActivityEntry::sole();
        $this->assertSame('reply.published', $entry->action);
        $this->assertSame('Ann', $entry->meta['author']);
        $this->assertSame('Downtown Cafe', $entry->meta['location']);
        $this->assertSame('manual', $entry->meta['source']);
    }

    public function test_action_lines_interpolate_meta(): void
    {
        $line = __('pages/activity.action_apikey_created', ['name' => 'CI key', 'scopes' => 'reviews:read']);

        $this->assertSame('Created API key "CI key" (reviews:read)', $line);
    }
}
