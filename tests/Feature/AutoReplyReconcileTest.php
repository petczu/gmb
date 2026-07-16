<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AutoReplyQueueItem;
use App\Models\Review;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * When a review gets a reply by any path (manual, AI, automation, MCP, or an
 * external reply reconciled from Google), still-open auto-reply queue items
 * for that review are retired — so they don't linger in "Scheduled" until
 * their post_at eventually arrives. The stale-scheduled clutter was the bug.
 */
class AutoReplyReconcileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id')->nullable();
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
            $table->string('mode')->default('auto');
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('post_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('auto_reply_queue');
        Schema::dropIfExists('reviews');
        parent::tearDown();
    }

    private function queueItem(int $reviewId, string $status): AutoReplyQueueItem
    {
        return AutoReplyQueueItem::create([
            'review_id' => $reviewId,
            'generated_text' => 'Thanks!',
            'status' => $status,
            'mode' => 'auto',
            'post_at' => now()->addDay(),
        ]);
    }

    public function test_replying_retires_scheduled_and_pending_items(): void
    {
        $review = Review::create(['reply_status' => null]);
        $scheduled = $this->queueItem($review->id, 'scheduled');
        $pending = $this->queueItem($review->id, 'pending');

        $review->update(['reply_text' => 'Cheers', 'reply_status' => 'published']);

        $this->assertSame('skipped', $scheduled->refresh()->status);
        $this->assertSame('skipped', $pending->refresh()->status);
        $this->assertNotNull($scheduled->refresh()->decided_at);
    }

    public function test_already_resolved_items_are_left_alone(): void
    {
        $review = Review::create(['reply_status' => null]);
        $published = $this->queueItem($review->id, 'published');
        $failed = $this->queueItem($review->id, 'failed');

        $review->update(['reply_status' => 'published']);

        // Only scheduled/pending are retired; terminal states stay as they are.
        $this->assertSame('published', $published->refresh()->status);
        $this->assertSame('failed', $failed->refresh()->status);
    }

    public function test_a_non_reply_update_does_not_touch_the_queue(): void
    {
        $review = Review::create(['reply_status' => null]);
        $scheduled = $this->queueItem($review->id, 'scheduled');

        // Touching an unrelated field must not retire the scheduled item.
        $review->update(['reply_source' => 'note']);

        $this->assertSame('scheduled', $scheduled->refresh()->status);
    }
}
