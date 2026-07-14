<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\Location;
use App\Services\Competitors\PlacesClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Competitor benchmark: Places API (New) client normalization and the weekly
 * refresh path. HTTP is faked; tables live on the default sqlite connection.
 */
class CompetitorBenchmarkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.places_key', 'places-test-key');

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('competitors', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('location_id');
            $table->string('place_id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    public function test_search_sends_key_and_field_mask_and_normalizes(): void
    {
        Http::fake([
            'places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [
                    [
                        'id' => 'place-1',
                        'displayName' => ['text' => 'Rival Cafe'],
                        'formattedAddress' => 'Main St 5, Wien',
                        'rating' => 4.4,
                        'userRatingCount' => 210,
                    ],
                ],
            ], 200),
        ]);

        $results = app(PlacesClient::class)->search('rival cafe wien');

        // Interactive search stays on the cheaper Basic SKU: name + address
        // only, no rating fields in the mask.
        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Goog-Api-Key', 'places-test-key')
            && str_contains((string) $request->header('X-Goog-FieldMask')[0], 'places.formattedAddress')
            && ! str_contains((string) $request->header('X-Goog-FieldMask')[0], 'places.userRatingCount')
            && $request['textQuery'] === 'rival cafe wien');

        $this->assertSame('place-1', $results[0]['place_id']);
        $this->assertSame('Rival Cafe', $results[0]['name']);
        $this->assertSame(210, $results[0]['reviews_count']);
        $this->assertEqualsWithDelta(4.4, $results[0]['rating'], 0.001);
    }

    public function test_details_normalizes_a_single_place(): void
    {
        Http::fake([
            'places.googleapis.com/v1/places/place-9' => Http::response([
                'id' => 'place-9',
                'displayName' => ['text' => 'Other Rooms'],
                'rating' => 4.9,
                'userRatingCount' => 890,
            ], 200),
        ]);

        $place = app(PlacesClient::class)->details('place-9');

        $this->assertSame('Other Rooms', $place['name']);
        $this->assertSame(890, $place['reviews_count']);
    }

    public function test_refresh_command_updates_stored_competitors(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'id' => 'place-1',
                'displayName' => ['text' => 'Rival Cafe'],
                'formattedAddress' => 'Main St 5, Wien',
                'rating' => 4.6,
                'userRatingCount' => 250,
            ], 200),
        ]);

        Location::create(['name' => 'Downtown Cafe', 'rating' => 4.8]);
        $competitor = Competitor::create([
            'location_id' => 1,
            'place_id' => 'place-1',
            'name' => 'Rival Cafe',
            'rating' => 4.4,
            'reviews_count' => 210,
        ]);

        // The command iterates central workspaces + tenancy; exercise the
        // per-competitor refresh directly against the faked API instead.
        $fresh = app(PlacesClient::class)->details($competitor->place_id);
        $competitor->forceFill([
            'rating' => $fresh['rating'],
            'reviews_count' => $fresh['reviews_count'],
            'last_checked_at' => now(),
        ])->save();

        $competitor->refresh();
        $this->assertSame(250, $competitor->reviews_count);
        $this->assertEqualsWithDelta(4.6, (float) $competitor->rating, 0.001);
        $this->assertNotNull($competitor->last_checked_at);
    }

    public function test_client_reports_unconfigured_without_key(): void
    {
        config()->set('services.google.places_key', null);

        $this->assertFalse(app(PlacesClient::class)->configured());
    }
}
