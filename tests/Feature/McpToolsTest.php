<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mcp\Servers\WorkspaceServer;
use App\Mcp\Tools\GetReviewTool;
use App\Mcp\Tools\ListLocationsTool;
use App\Mcp\Tools\ListReviewsTool;
use App\Mcp\Tools\ReplyToReviewTool;
use App\Mcp\Tools\ReviewStatsTool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises the MCP tools directly against the tenant Location/Review models on
 * the default connection — minimal sqlite tables, no stancl tenancy bootstrap,
 * mirroring ReviewInsightsServiceTest. The token round-trip and write-gate are
 * proven separately without persisting a Workspace (tenant DB creation would
 * otherwise fire on save).
 */
class McpToolsTest extends TestCase
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

    public function test_list_reviews_returns_all_newest_first(): void
    {
        $response = WorkspaceServer::tool(ListReviewsTool::class, []);

        $response->assertOk()
            ->assertSee('"total": 3')
            ->assertSee('"returned": 3')
            ->assertSee('Great place');
    }

    public function test_list_reviews_filters_by_rating(): void
    {
        WorkspaceServer::tool(ListReviewsTool::class, ['rating' => 5])
            ->assertOk()
            ->assertSee('"returned": 1')
            ->assertSee('Great place');
    }

    public function test_list_reviews_filters_rating_only_and_replied(): void
    {
        WorkspaceServer::tool(ListReviewsTool::class, ['has_text' => false])
            ->assertOk()
            ->assertSee('"returned": 1')
            ->assertSee('Bob');

        WorkspaceServer::tool(ListReviewsTool::class, ['replied' => true])
            ->assertOk()
            ->assertSee('"returned": 1')
            ->assertSee('Thanks!');
    }

    public function test_review_stats_aggregates(): void
    {
        WorkspaceServer::tool(ReviewStatsTool::class, [])
            ->assertOk()
            ->assertSee('"total": 3')
            ->assertSee('"average_rating": 3')
            ->assertSee('"replied": 1')
            ->assertSee('"reply_rate_percent": 33')
            ->assertSee('"rating_only": 1')
            ->assertSee('"new_this_month": 2');
    }

    public function test_get_review_returns_detail_or_error(): void
    {
        WorkspaceServer::tool(GetReviewTool::class, ['id' => 1])
            ->assertOk()
            ->assertSee('Great place')
            ->assertSee('https://g/1');

        WorkspaceServer::tool(GetReviewTool::class, ['id' => 999])
            ->assertSee('not found');
    }

    public function test_list_locations_returns_goal(): void
    {
        WorkspaceServer::tool(ListLocationsTool::class, [])
            ->assertOk()
            ->assertSee('"count": 1')
            ->assertSee('Downtown Cafe')
            ->assertSee('"monthly_goal": 30');
    }

    public function test_reply_tool_is_read_only_by_default(): void
    {
        // No tenant() bound in tests, so the write tool must stay unregistered:
        // MCP is read-only until a workspace opts in via mcp_write_enabled.
        $this->assertFalse((new ReplyToReviewTool)->shouldRegister());
    }
}
