<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Services\Reports\ReportData;
use App\Support\DashboardPeriod;
use App\Support\ReportBlocks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The report's Google-posts block: published-in-window counts with a
 * previous-period comparison, media share, type breakdown and the recent
 * list; skipped entirely when the workspace never published a post.
 */
class ReportPostsBlockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function ($table): void {
            $table->increments('id');
            $table->string('type', 20);
            $table->text('caption')->nullable();
            $table->string('title')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->json('location_ids');
            $table->json('source_ids');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('uid', 32)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('posts');
        parent::tearDown();
    }

    private function period(array $locationIds = []): DashboardPeriod
    {
        return new DashboardPeriod(
            start: CarbonImmutable::parse('2026-07-01'),
            end: CarbonImmutable::parse('2026-07-31 23:59:59'),
            prevStart: CarbonImmutable::parse('2026-06-01'),
            prevEnd: CarbonImmutable::parse('2026-06-30 23:59:59'),
            compare: true,
            locationId: null,
            preset: 'last_30',
            locationIds: $locationIds,
        );
    }

    private function posts(DashboardPeriod $period): array
    {
        $data = app(ReportData::class);

        return (new ReflectionMethod($data, 'posts'))->invoke($data, $period);
    }

    public function test_posts_block_is_registered(): void
    {
        $this->assertContains('posts', ReportBlocks::ORDER);
        $this->assertContains('posts', ReportBlocks::presets()['standard']);
        $this->assertContains('posts', ReportBlocks::presets()['full']);
    }

    public function test_it_is_skipped_when_nothing_was_ever_published(): void
    {
        Post::create(['type' => 'update', 'caption' => 'Draft only', 'location_ids' => [1], 'source_ids' => [], 'status' => 'draft', 'scheduled_at' => '2026-07-10 10:00:00']);

        $this->assertSame([], $this->posts($this->period()));
    }

    public function test_it_counts_published_posts_in_the_window_with_prev_delta(): void
    {
        $base = ['location_ids' => [1], 'source_ids' => [], 'status' => 'published'];
        Post::create($base + ['type' => 'update', 'caption' => 'July A', 'image_url' => 'https://x/i.jpg', 'scheduled_at' => '2026-07-05 10:00:00']);
        Post::create($base + ['type' => 'update', 'caption' => 'July B', 'video_url' => 'https://x/v.mp4', 'scheduled_at' => '2026-07-20 10:00:00']);
        Post::create($base + ['type' => 'offer', 'title' => 'July offer', 'scheduled_at' => '2026-07-21 10:00:00']);
        Post::create($base + ['type' => 'update', 'caption' => 'June', 'scheduled_at' => '2026-06-15 10:00:00']);
        Post::create($base + ['type' => 'update', 'caption' => 'Out of range', 'scheduled_at' => '2026-05-01 10:00:00']);

        $posts = $this->posts($this->period());

        $this->assertSame(3, $posts['total']);
        $this->assertSame(1, $posts['prev']);
        $this->assertSame(2, $posts['withMedia']);
        $this->assertSame(['update' => 2, 'offer' => 1], $posts['byType']);
        $this->assertCount(3, $posts['recent']);
        // Newest first; offers surface their title as the caption.
        $this->assertSame('July offer', $posts['recent'][0]['caption']);
        $this->assertTrue($posts['recent'][2]['hasMedia']);
    }

    public function test_it_respects_the_period_location_selection(): void
    {
        $base = ['source_ids' => [], 'status' => 'published', 'type' => 'update', 'scheduled_at' => '2026-07-05 10:00:00'];
        Post::create($base + ['caption' => 'Loc 1', 'location_ids' => [1]]);
        Post::create($base + ['caption' => 'Loc 2', 'location_ids' => [2]]);

        $posts = $this->posts($this->period(locationIds: [2]));

        $this->assertSame(1, $posts['total']);
        $this->assertSame('Loc 2', $posts['recent'][0]['caption']);
    }
}
