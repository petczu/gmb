<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AutoReplyQueueItem;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Retrying a failed reply that already produced text re-posts that draft (no
 * fresh AI generation) and marks it published. A provider error on retry
 * propagates so the UI can surface it.
 */
class AutoReplyRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id')->nullable();
            $table->string('external_review_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('text')->nullable();
            $table->text('reply_text')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->string('reply_status')->nullable();
            $table->string('reply_source')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_reply_queue', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->text('generated_text')->nullable();
            $table->string('status');
            $table->string('mode');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();
            $table->integer('credits_spent')->default(0);
            $table->text('error')->nullable();
            $table->unsignedInteger('decided_by')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('post_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('auto_reply_queue');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        Mockery::close();
        parent::tearDown();
    }

    public function test_retry_reposts_the_existing_draft_and_marks_it_published(): void
    {
        Location::create(['name' => 'Dubai', 'zernio_account_id' => 'acc-1', 'external_id' => 'loc-1']);
        $review = Review::create([
            'location_id' => 1,
            'rating' => 5,
            'text' => 'Amazing!',
            'external_review_id' => 'r-1',
        ]);
        $failed = AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => 'Thanks so much!',
            'status' => 'failed',
            'mode' => 'auto',
            'error' => 'AI provider [anthropic] is overloaded.',
        ]);

        $provider = Mockery::mock(ReviewProvider::class);
        $provider->shouldReceive('reply')->once()->with('acc-1', 'r-1', 'Thanks so much!', 'loc-1');

        $factory = Mockery::mock(ReviewProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);
        $this->app->instance(ReviewProviderFactory::class, $factory);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        $item = app(AutomationService::class)->retry($workspace, $review);

        $this->assertNotNull($item);
        $this->assertSame('published', $item->fresh()->status);
        $this->assertNull($item->fresh()->error);
        $this->assertSame('Thanks so much!', $review->fresh()->reply_text);
        $this->assertSame('published', $review->fresh()->reply_status);
        // No new queue item was created: the same failed row was reused.
        $this->assertSame(1, AutoReplyQueueItem::count());
        $this->assertSame($failed->id, $item->id);
    }

    public function test_retry_does_nothing_when_the_review_is_already_replied(): void
    {
        Location::create(['name' => 'Dubai', 'zernio_account_id' => 'acc-1']);
        $review = Review::create([
            'location_id' => 1,
            'rating' => 5,
            'text' => 'Amazing!',
            'external_review_id' => 'r-1',
            'reply_text' => 'Already answered',
        ]);
        AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => 'Thanks!',
            'status' => 'failed',
            'mode' => 'auto',
        ]);

        $factory = Mockery::mock(ReviewProviderFactory::class);
        $factory->shouldReceive('make')->never();
        $this->app->instance(ReviewProviderFactory::class, $factory);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        $this->assertNull(app(AutomationService::class)->retry($workspace, $review));
    }

    public function test_retry_propagates_a_provider_failure(): void
    {
        Location::create(['name' => 'Dubai', 'zernio_account_id' => 'acc-1', 'external_id' => 'loc-1']);
        $review = Review::create([
            'location_id' => 1,
            'rating' => 5,
            'text' => 'Amazing!',
            'external_review_id' => 'r-1',
        ]);
        AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => 'Thanks!',
            'status' => 'failed',
            'mode' => 'auto',
        ]);

        $provider = Mockery::mock(ReviewProvider::class);
        $provider->shouldReceive('reply')->once()->andThrow(new RuntimeException('[404] not found'));

        $factory = Mockery::mock(ReviewProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);
        $this->app->instance(ReviewProviderFactory::class, $factory);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        $this->expectException(RuntimeException::class);
        app(AutomationService::class)->retry($workspace, $review);
    }
}
