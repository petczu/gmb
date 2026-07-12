<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\AutoReplyQueueItem;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\AutomationService;
use App\Services\Ai\ReplyGenerator;
use App\Services\Ai\ReplyScheduler;
use App\Services\Billing\AiUsageService;
use App\Services\Reviews\ReviewProviderFactory;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * The automations engine's review-date window: the scheduled safety-net pass
 * runs with --since so a freshly connected location's multi-year backlog is
 * never mass-replied, while fresh reviews get their queue item.
 */
class AutomationRunWindowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('automations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('trigger')->default('new_review');
            $table->json('rating_filter')->nullable();
            $table->boolean('all_locations')->default(true);
            $table->json('location_ids')->nullable();
            $table->boolean('respect_working_hours')->default(false);
            $table->integer('reply_delay_min_minutes')->default(0);
            $table->integer('reply_delay_max_minutes')->default(0);
            $table->json('working_hours')->nullable();
            $table->boolean('reply_to_previous')->default(false);
            $table->boolean('approve_before_posting')->default(true);
            $table->string('content_type')->default('default');
            $table->text('default_message')->nullable();
            $table->unsignedInteger('ai_agent_id')->nullable();
            $table->timestamps();
        });

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
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('text')->nullable();
            $table->text('reply_text')->nullable();
            $table->dateTime('created_at_external')->nullable();
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
        Schema::dropIfExists('automations');
        Mockery::close();
        parent::tearDown();
    }

    public function test_since_window_protects_the_backlog(): void
    {
        Automation::create([
            'name' => 'Sara',
            'enabled' => true,
            'trigger' => 'new_review',
            'all_locations' => true,
            'approve_before_posting' => true,
            'content_type' => 'default',
            'default_message' => 'Thank you for visiting us!',
        ]);

        Location::create(['name' => 'City Walk']);

        $fresh = Review::create([
            'location_id' => 1, 'rating' => 5, 'text' => 'Great!',
            'external_review_id' => 'r-fresh', 'created_at_external' => now()->subHours(2),
        ]);
        $backlog = Review::create([
            'location_id' => 1, 'rating' => 5, 'text' => 'Old but gold',
            'external_review_id' => 'r-old', 'created_at_external' => now()->subDays(300),
        ]);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        // The pending-approvals email writes to the central workspace row, which
        // this lightweight test does not set up — stub it out, it is covered by
        // the notification's own path.
        $service = Mockery::mock(AutomationService::class, [
            app(ReplyGenerator::class),
            app(AiCreditService::class),
            app(ReviewProviderFactory::class),
            app(AiUsageService::class),
            app(ReplyScheduler::class),
        ])->makePartial();
        $service->shouldReceive('notifyPendingApprovals')->andReturnNull();

        // Windowed pass (the scheduled --since=48): only the fresh review.
        $stats = $service->processWorkspace($workspace, now()->subHours(48)->toImmutable());

        $this->assertSame(1, $stats['generated']);
        $this->assertSame(1, $stats['queued']);
        $this->assertSame(1, AutoReplyQueueItem::query()->where('review_id', $fresh->id)->where('status', 'pending')->count());
        $this->assertSame(0, AutoReplyQueueItem::query()->where('review_id', $backlog->id)->count());

        // Unwindowed pass (explicit manual run) picks up the backlog too.
        $stats = $service->processWorkspace($workspace);

        $this->assertSame(1, $stats['generated']);
        $this->assertSame(1, AutoReplyQueueItem::query()->where('review_id', $backlog->id)->where('status', 'pending')->count());
    }

    /**
     * A review whose publish FAILED (e.g. deleted on Google → 404) must be
     * parked, not regenerated on every pass — that would burn the AI allowance
     * in a loop (observed in production: six drafts for one dead review).
     */
    public function test_a_failed_publish_parks_the_review_instead_of_regenerating(): void
    {
        Automation::create([
            'name' => 'Sara',
            'enabled' => true,
            'trigger' => 'new_review',
            'all_locations' => true,
            'approve_before_posting' => true,
            'content_type' => 'default',
            'default_message' => 'Thank you for visiting us!',
        ]);

        Location::create(['name' => 'City Walk']);

        $review = Review::create([
            'location_id' => 1, 'rating' => 5, 'text' => 'Gone on Google',
            'external_review_id' => 'r-deleted', 'created_at_external' => now()->subHours(2),
        ]);

        AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => 'Draft that could not be posted',
            'status' => 'failed',
            'mode' => 'auto',
            'error' => '[404] GBP resource not found',
        ]);

        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        $service = Mockery::mock(AutomationService::class, [
            app(ReplyGenerator::class),
            app(AiCreditService::class),
            app(ReviewProviderFactory::class),
            app(AiUsageService::class),
            app(ReplyScheduler::class),
        ])->makePartial();
        $service->shouldReceive('notifyPendingApprovals')->andReturnNull();

        $stats = $service->processWorkspace($workspace);

        $this->assertSame(0, $stats['generated']);
        $this->assertSame(1, AutoReplyQueueItem::query()->where('review_id', $review->id)->count());
    }
}
