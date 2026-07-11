<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AutoReplyQueueItem;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutoReplyService;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Approving a draft whose review no longer exists on Google (Zernio 404) must
 * park the queue item as `failed` with the reason, keep the review unreplied,
 * and rethrow so the UI can explain the failure. A stuck `pending` item was
 * the original bug.
 */
class AutoReplyApproveFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('zernio_account_id')->nullable();
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

    public function test_publish_failure_marks_the_draft_failed_and_rethrows(): void
    {
        Location::create(['name' => 'Riyadh', 'zernio_account_id' => 'acc-1']);
        $review = Review::create([
            'location_id' => 1,
            'rating' => 5,
            'text' => 'Great!',
            'external_review_id' => 'r-deleted',
        ]);
        $item = AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => 'Thank you!',
            'status' => 'pending',
            'mode' => 'draft',
        ]);

        $provider = Mockery::mock(ReviewProvider::class);
        $provider->shouldReceive('reply')->once()->andThrow(
            new RuntimeException('[404] The requested Google Business Profile resource was not found.'),
        );

        $factory = Mockery::mock(ReviewProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);
        $this->app->instance(ReviewProviderFactory::class, $factory);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        try {
            app(AutoReplyService::class)->approve($workspace, $item, 7);
            $this->fail('approve() should rethrow the publish failure');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('404', $e->getMessage());
        }

        $item->refresh();
        $this->assertSame('failed', $item->status);
        $this->assertStringContainsString('not found', $item->error);
        $this->assertSame(7, (int) $item->decided_by);
        $this->assertNotNull($item->decided_at);

        // The review itself must stay unreplied so a regenerated draft can retry.
        $this->assertNull($review->fresh()->reply_text);
    }
}
