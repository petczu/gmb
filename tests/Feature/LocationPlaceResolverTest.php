<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Services\Competitors\LocationPlaceResolver;
use App\Services\Competitors\PlacesClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Resolving a connected location's Google place_id from a Places Text Search
 * on its name + address, so competitors:refresh can reuse the synced data.
 */
class LocationPlaceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.places_key', 'test-key');

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('place_id')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    private function fakePlaces(array $places): void
    {
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => $places])]);
    }

    public function test_prefers_name_match_over_first_result(): void
    {
        $this->fakePlaces([
            ['id' => 'ChIJ-wrong', 'displayName' => ['text' => 'Some Other Cafe'], 'formattedAddress' => 'X', 'rating' => 3.0, 'userRatingCount' => 5],
            ['id' => 'ChIJ-right', 'displayName' => ['text' => 'NoWayOut Escape Rooms Riyadh'], 'formattedAddress' => 'Hittin, Riyadh', 'rating' => 5.0, 'userRatingCount' => 319],
        ]);

        $location = Location::create(['name' => 'NoWayOut Escape Rooms Riyadh', 'address' => 'Hittin, Riyadh']);

        $placeId = app(LocationPlaceResolver::class)->resolve($location);

        $this->assertSame('ChIJ-right', $placeId);
        $this->assertSame('ChIJ-right', $location->refresh()->place_id);
    }

    public function test_falls_back_to_first_result_when_no_name_overlap(): void
    {
        $this->fakePlaces([
            ['id' => 'ChIJ-top', 'displayName' => ['text' => 'Totally Different'], 'formattedAddress' => 'Y', 'rating' => 4.0, 'userRatingCount' => 10],
        ]);

        $location = Location::create(['name' => 'Mystery Rooms', 'address' => 'Somewhere']);

        $this->assertSame('ChIJ-top', app(LocationPlaceResolver::class)->resolve($location));
    }

    public function test_already_resolved_location_is_left_alone(): void
    {
        Http::fake();
        $location = Location::create(['name' => 'X', 'address' => 'Y', 'place_id' => 'ChIJ-existing']);

        $this->assertSame('ChIJ-existing', app(LocationPlaceResolver::class)->resolve($location));
        Http::assertNothingSent();
    }

    public function test_unconfigured_or_no_match_returns_null(): void
    {
        $this->fakePlaces([]);
        $location = Location::create(['name' => 'Nowhere', 'address' => 'Noplace']);

        $this->assertNull(app(LocationPlaceResolver::class)->resolve($location));
        $this->assertNull($location->refresh()->place_id);

        config()->set('services.google.places_key', null);
        $this->assertFalse(app(PlacesClient::class)->configured());
        $this->assertNull(app(LocationPlaceResolver::class)->resolve(Location::create(['name' => 'A', 'address' => 'B'])));
    }
}
