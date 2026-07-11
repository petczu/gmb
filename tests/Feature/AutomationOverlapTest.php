<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\Location;
use App\Models\Review;
use App\Services\Ai\AutomationService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Overlapping automations resolve deterministically: specific-location
 * automations override "All locations" catch-alls regardless of creation
 * order, and overlap detection (used for the save-time warning) requires both
 * the location scope and the rating scope to intersect.
 */
class AutomationOverlapTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('text')->nullable();
            $table->text('reply_text')->nullable();
            $table->dateTime('created_at_external')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('automations');
        parent::tearDown();
    }

    public function test_specific_location_automation_beats_an_older_catch_all(): void
    {
        Location::create(['name' => 'Dubai']);

        // The catch-all is OLDER (lower id) but must still lose to the override.
        Automation::create(['name' => 'Catch-all', 'all_locations' => true, 'default_message' => 'x']);
        Automation::create(['name' => 'Dubai only', 'all_locations' => false, 'location_ids' => [1], 'default_message' => 'x']);

        $review = Review::create(['location_id' => 1, 'rating' => 5]);

        $winner = app(AutomationService::class)->matching($review);

        $this->assertSame('Dubai only', $winner?->name);
    }

    public function test_overlap_requires_both_location_and_rating_intersection(): void
    {
        $fiveStars = Automation::create(['name' => 'Praise', 'all_locations' => false, 'location_ids' => [1], 'rating_filter' => [5, 4], 'default_message' => 'x']);
        $lowStars = Automation::create(['name' => 'Crisis', 'all_locations' => false, 'location_ids' => [1], 'rating_filter' => [1, 2], 'default_message' => 'x']);
        $otherLocation = Automation::create(['name' => 'Elsewhere', 'all_locations' => false, 'location_ids' => [2], 'rating_filter' => [5], 'default_message' => 'x']);
        $catchAll = Automation::create(['name' => 'Any', 'all_locations' => true, 'rating_filter' => null, 'default_message' => 'x']);

        // Same location, disjoint ratings: the intended split setup, no overlap.
        $this->assertFalse($fiveStars->overlapsWith($lowStars));

        // Different locations: no overlap even with the same rating.
        $this->assertFalse($fiveStars->overlapsWith($otherLocation));

        // A catch-all with no rating filter overlaps everything.
        $this->assertTrue($fiveStars->overlapsWith($catchAll));
        $this->assertTrue($lowStars->overlapsWith($catchAll));

        // overlapping() excludes self and disabled automations.
        $catchAll->update(['enabled' => false]);
        $this->assertSame([], $fiveStars->overlapping()->pluck('name')->all());
    }
}
