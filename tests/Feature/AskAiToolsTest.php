<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Ai\Agents\WorkspaceAnalyst;
use App\Ai\Tools\ListLocations;
use App\Ai\Tools\ListReviews;
use App\Ai\Tools\ReviewStats;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Exercises the Ask AI agent tools directly against the tenant Location/Review
 * models on the default connection — minimal sqlite tables, no stancl tenancy
 * bootstrap, mirroring McpToolsTest. The agent itself is proven with a fake.
 */
class AskAiToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('review_goal')->nullable();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->text('reply_text')->nullable();
            $table->string('reply_status')->nullable();
            $table->string('review_link')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->dateTime('created_at_external');
        });

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-30 12:00:00'));

        DB::table('locations')->insert([
            'id' => 1, 'name' => 'Downtown Cafe', 'address' => '1 Main St',
            'rating' => 4.50, 'reviews_count' => 3, 'review_goal' => 30,
        ]);

        DB::table('reviews')->insert([
            ['id' => 1, 'location_id' => 1, 'author_name' => 'Ann', 'rating' => 5, 'text' => 'Great place', 'reply_text' => 'Thanks!', 'reply_status' => 'published', 'review_link' => 'https://g/1', 'replied_at' => '2026-06-21 09:00:00', 'created_at_external' => '2026-06-20 10:00:00'],
            ['id' => 2, 'location_id' => 1, 'author_name' => 'Bob', 'rating' => 3, 'text' => '', 'reply_text' => null, 'reply_status' => null, 'review_link' => 'https://g/2', 'replied_at' => null, 'created_at_external' => '2026-06-10 10:00:00'],
            ['id' => 3, 'location_id' => 1, 'author_name' => 'Cy', 'rating' => 1, 'text' => 'Bad', 'reply_text' => null, 'reply_status' => null, 'review_link' => 'https://g/3', 'replied_at' => null, 'created_at_external' => '2026-05-01 10:00:00'],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    public function test_list_locations_returns_workspace_locations(): void
    {
        $result = json_decode((string) (new ListLocations)->handle(new Request), true);

        $this->assertCount(1, $result);
        $this->assertSame('Downtown Cafe', $result[0]['name']);
    }

    public function test_list_reviews_returns_all_newest_first(): void
    {
        $result = json_decode((string) (new ListReviews)->handle(new Request), true);

        $this->assertCount(3, $result);
        $this->assertSame(['Ann', 'Bob', 'Cy'], array_column($result, 'author'));
    }

    public function test_list_reviews_filters_by_rating_replied_and_text(): void
    {
        $rated = json_decode((string) (new ListReviews)->handle(new Request(['rating' => 5])), true);
        $this->assertSame(['Ann'], array_column($rated, 'author'));

        $unreplied = json_decode((string) (new ListReviews)->handle(new Request(['replied' => false])), true);
        $this->assertSame(['Bob', 'Cy'], array_column($unreplied, 'author'));

        $withText = json_decode((string) (new ListReviews)->handle(new Request(['has_text' => true])), true);
        $this->assertSame(['Ann', 'Cy'], array_column($withText, 'author'));
    }

    public function test_list_reviews_filters_by_date_range(): void
    {
        $result = json_decode((string) (new ListReviews)->handle(new Request([
            'from' => '2026-06-01',
            'to' => '2026-06-15',
        ])), true);

        $this->assertSame(['Bob'], array_column($result, 'author'));
    }

    public function test_review_stats_reports_totals(): void
    {
        $result = json_decode((string) (new ReviewStats)->handle(new Request), true);

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['replied']);
        $this->assertSame(33, $result['reply_rate_percent']);
        $this->assertSame(1, $result['rating_only']);
        $this->assertSame(2, $result['new_this_month']);
        $this->assertEqualsWithDelta(3.0, $result['average_rating'], 0.01);
    }

    public function test_agent_answers_with_history_via_fake(): void
    {
        WorkspaceAnalyst::fake(['You got 3 reviews this month.']);

        $response = (new WorkspaceAnalyst([
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'assistant', 'content' => 'Hello'],
        ]))->prompt('How many reviews this month?');

        $this->assertSame('You got 3 reviews this month.', $response->text);
        WorkspaceAnalyst::assertPrompted('How many reviews this month?');
    }
}
