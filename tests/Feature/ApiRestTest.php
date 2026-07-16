<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Api\ApiAbilities;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\RequireApiAbility;
use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The REST controllers query the tenant Location/Review models on the default
 * connection, so we build minimal sqlite tables and call the controllers
 * directly — no tenancy bootstrap (mirrors McpToolsTest). Auth/ability gating is
 * covered without persisting the central ApiKey (which is pinned to mysql).
 */
class ApiRestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('zernio_account_id')->nullable();
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
            $table->string('reply_source')->nullable();
            $table->string('review_link')->nullable();
            $table->string('external_review_id')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->dateTime('created_at_external');
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

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-30 12:00:00'));

        DB::table('locations')->insert([
            'id' => 1, 'name' => 'Downtown Cafe', 'address' => '1 Main St',
            'zernio_account_id' => 'acc-1', 'rating' => 4.50, 'reviews_count' => 3, 'review_goal' => 30,
        ]);

        DB::table('reviews')->insert([
            ['id' => 1, 'location_id' => 1, 'author_name' => 'Ann', 'rating' => 5, 'text' => 'Great place', 'reply_text' => 'Thanks!', 'reply_status' => 'published', 'reply_source' => 'manual', 'external_review_id' => 'ext-1', 'review_link' => 'https://g/1', 'replied_at' => '2026-06-21 09:00:00', 'created_at_external' => '2026-06-20 10:00:00'],
            ['id' => 2, 'location_id' => 1, 'author_name' => 'Bob', 'rating' => 3, 'text' => '', 'reply_text' => null, 'reply_status' => null, 'reply_source' => null, 'external_review_id' => 'ext-2', 'review_link' => 'https://g/2', 'replied_at' => null, 'created_at_external' => '2026-06-10 10:00:00'],
            ['id' => 3, 'location_id' => 1, 'author_name' => 'Cy', 'rating' => 1, 'text' => 'Bad', 'reply_text' => null, 'reply_status' => null, 'reply_source' => null, 'external_review_id' => 'ext-3', 'review_link' => 'https://g/3', 'replied_at' => null, 'created_at_external' => '2026-05-01 10:00:00'],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    public function test_locations_endpoint_returns_all(): void
    {
        $data = (new LocationController)->index()->resolve();

        $this->assertCount(1, $data);
        $this->assertSame('Downtown Cafe', $data[0]['name']);
        $this->assertSame(30, $data[0]['monthly_goal']);
    }

    public function test_reviews_index_filters_by_rating(): void
    {
        $data = (new ReviewController)->index(Request::create('/', 'GET', ['rating' => 5]))->resolve();

        $this->assertCount(1, $data);
        $this->assertSame('Great place', $data[0]['text']);
    }

    public function test_reviews_index_filters_rating_only(): void
    {
        $data = (new ReviewController)->index(Request::create('/', 'GET', ['has_text' => '0']))->resolve();

        $this->assertCount(1, $data);
        $this->assertSame('Bob', $data[0]['author']);
    }

    public function test_review_show_returns_detail_and_404(): void
    {
        $ok = (new ReviewController)->show(1);
        $this->assertSame('Great place', $ok->resolve()['text']);

        $missing = (new ReviewController)->show(999);
        $this->assertSame(404, $missing->status());
    }

    public function test_reply_publishes_and_tags_source_api(): void
    {
        // Never hit the real provider in tests — the fake reply is a no-op.
        config(['services.reviews.driver' => 'fake']);

        $result = (new ReviewController)->reply(Request::create('/', 'POST', ['reply' => 'Thank you!']), 3);

        $data = $result->resolve();
        $this->assertSame('Thank you!', $data['reply']);
        $this->assertSame('api', $data['reply_source']);
        $this->assertSame('published', DB::table('reviews')->where('id', 3)->value('reply_status'));
    }

    public function test_stats_aggregates(): void
    {
        $data = (new StatsController)->index(Request::create('/', 'GET'))->getData(true);

        $this->assertSame(3, $data['total']);
        $this->assertSame(1, $data['replied']);
        $this->assertSame(33, $data['reply_rate_percent']);
        $this->assertSame(1, $data['rating_only']);
        $this->assertSame(2, $data['new_this_month']);
    }

    public function test_ability_middleware_enforces_scope(): void
    {
        $key = (new ApiKey)->forceFill(['abilities' => [ApiAbilities::REVIEWS_READ]]);
        $request = Request::create('/');
        $request->attributes->set(AuthenticateApiKey::REQUEST_KEY, $key);

        $mw = new RequireApiAbility;

        $allowed = $mw->handle($request, fn () => response('ok'), ApiAbilities::REVIEWS_READ);
        $this->assertSame(200, $allowed->getStatusCode());

        $blocked = $mw->handle($request, fn () => response('ok'), ApiAbilities::REVIEWS_REPLY);
        $this->assertSame(403, $blocked->getStatusCode());
    }

    public function test_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/locations')->assertStatus(401);
    }

    public function test_abilities_catalogue(): void
    {
        $this->assertTrue(ApiAbilities::isValid('reviews:reply'));
        $this->assertFalse(ApiAbilities::isValid('reviews:delete'));
        $this->assertCount(4, ApiAbilities::all());
    }
}
