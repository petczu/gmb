<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlaceReview;
use App\Services\Competitors\DataForSeoReviewsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * DataForSEO Google Reviews backfill: normalizes individual reviews (with
 * timestamps) and upserts them into the central place_reviews table, deduping
 * by (place_id, review_id) across runs.
 */
class CompetitorReviewsBackfillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.dataforseo.login', 'login');
        config()->set('services.dataforseo.password', 'pass');
        config()->set('services.dataforseo.reviews_enabled', true);

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('place_reviews', function ($table): void {
            $table->increments('id');
            $table->string('place_id');
            $table->string('review_id');
            $table->decimal('rating', 2, 1)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('author')->nullable();
            $table->text('text')->nullable();
            $table->string('language', 8)->nullable();
            $table->timestamps();
            $table->unique(['place_id', 'review_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('mysql')->dropIfExists('place_reviews');
        parent::tearDown();
    }

    private function fakeReviews(array $items): void
    {
        Http::fake([
            '*/reviews/task_post' => Http::response([
                'tasks' => [['id' => 'task-1', 'status_code' => 20100, 'status_message' => 'Task Created.']],
            ]),
            '*/reviews/task_get/*' => Http::response([
                'tasks' => [['id' => 'task-1', 'status_code' => 20000, 'result' => [['items' => $items]]]],
            ]),
        ]);
    }

    public function test_client_normalizes_review_items(): void
    {
        $this->fakeReviews([[
            'review_id' => 'r1',
            'rating' => ['value' => 5],
            'timestamp' => '2026-01-05 14:09:32 +00:00',
            'profile_name' => 'Jane D.',
            'review_text' => 'Fantastic room',
            'language' => 'en',
        ]]);

        $reviews = app(DataForSeoReviewsClient::class)->fetch('place-x', 100);

        $this->assertCount(1, $reviews);
        $this->assertSame('r1', $reviews[0]['review_id']);
        $this->assertSame(5.0, $reviews[0]['rating']);
        $this->assertSame('2026-01-05', $reviews[0]['reviewed_at']->toDateString());
        $this->assertSame('Jane D.', $reviews[0]['author']);
        $this->assertSame('Fantastic room', $reviews[0]['text']);
    }

    public function test_backfill_stores_and_dedupes_reviews(): void
    {
        $this->fakeReviews([
            ['review_id' => 'r1', 'rating' => ['value' => 5], 'timestamp' => '2026-06-01 10:00:00 +00:00', 'profile_name' => 'A', 'review_text' => 'Good'],
            ['review_id' => 'r2', 'rating' => ['value' => 4], 'timestamp' => '2026-06-02 10:00:00 +00:00', 'profile_name' => 'B', 'review_text' => 'Nice'],
        ]);

        $this->artisan('competitors:backfill-reviews', ['--place' => ['place-x']])->assertSuccessful();
        $this->assertSame(2, PlaceReview::query()->count());

        // Re-running upserts, not duplicates.
        $this->artisan('competitors:backfill-reviews', ['--place' => ['place-x']])->assertSuccessful();
        $this->assertSame(2, PlaceReview::query()->count());
    }

    public function test_backfill_is_a_noop_when_reviews_are_disabled(): void
    {
        config()->set('services.dataforseo.reviews_enabled', false);
        Http::fake();

        $this->artisan('competitors:backfill-reviews', ['--place' => ['place-x']])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, PlaceReview::query()->count());
    }
}
