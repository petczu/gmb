<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Services\Listings\ListingUpdater;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Google Business profile editing via Zernio's native gmb-location-details
 * PATCH: form → payload mapping (with updateMask) and the local listing_data
 * copy kept as an offline prefill fallback.
 */
class ListingUpdaterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reviews.zernio_base_url', 'https://zernio.test/api/v1');
        config()->set('services.reviews.zernio_key', 'test-key');

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->json('listing_data')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    public function test_payload_maps_hours_basics_and_update_mask(): void
    {
        $payload = app(ListingUpdater::class)->buildPayload([
            'description' => 'Best escape rooms in town.',
            'phone' => '0676 6338668',
            'website' => 'https://example.com',
            'opening_hours' => [
                ['day' => 'MONDAY', 'open' => '09:00', 'close' => '18:30'],
                ['day' => 'SATURDAY', 'open' => '10:00:00', 'close' => '16:00:00'],
            ],
            'special_hours' => [
                ['start_date' => '2026-12-24', 'end_date' => '2026-12-26', 'closed' => true],
                ['start_date' => '2026-12-31', 'end_date' => '2026-12-31', 'closed' => false, 'open' => '10:00', 'close' => '14:00'],
            ],
        ]);

        $this->assertSame(
            'profile.description,phoneNumbers,websiteUri,regularHours,specialHours',
            $payload['updateMask'],
        );
        $this->assertSame(['description' => 'Best escape rooms in town.'], $payload['profile']);
        $this->assertSame(['primaryPhone' => '0676 6338668'], $payload['phoneNumbers']);
        $this->assertSame('https://example.com', $payload['websiteUri']);

        $this->assertSame([
            'openDay' => 'MONDAY',
            'openTime' => '09:00',
            'closeDay' => 'MONDAY',
            'closeTime' => '18:30',
        ], $payload['regularHours']['periods'][0]);
        $this->assertSame('16:00', $payload['regularHours']['periods'][1]['closeTime']);

        $special = $payload['specialHours']['specialHourPeriods'];
        $this->assertSame(['year' => 2026, 'month' => 12, 'day' => 24], $special[0]['startDate']);
        $this->assertTrue($special[0]['closed']);
        $this->assertArrayNotHasKey('openTime', $special[0]);
        $this->assertSame('14:00', $special[1]['closeTime']);
        $this->assertFalse($special[1]['closed']);
    }

    public function test_payload_skips_blank_and_incomplete_rows(): void
    {
        $payload = app(ListingUpdater::class)->buildPayload([
            'description' => null,
            'opening_hours' => [['day' => 'MONDAY', 'open' => null, 'close' => '18:00']],
            'special_hours' => [['start_date' => '2026-12-31', 'end_date' => '2026-12-31', 'closed' => false]],
        ]);

        $this->assertSame([], $payload);
    }

    public function test_push_patches_location_details_and_stores_local_copy(): void
    {
        Http::fake(['zernio.test/*' => Http::response(['success' => true], 200)]);

        $location = Location::create([
            'name' => 'Downtown Cafe',
            'external_id' => '1185302053868319269',
            'zernio_account_id' => 'acc-1',
        ]);

        app(ListingUpdater::class)->push($location, [
            'description' => 'Cozy cafe.',
            'phone' => '+43 1 234',
            'website' => 'https://cafe.example',
            'opening_hours' => [['day' => 'FRIDAY', 'open' => '08:00', 'close' => '20:00']],
            'special_hours' => [],
        ]);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/accounts/acc-1/gmb-location-details')
                && str_contains($request->url(), 'locationId=1185302053868319269')
                && $request->method() === 'PATCH'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && str_contains((string) $request['updateMask'], 'regularHours')
                && $request['profile'] === ['description' => 'Cozy cafe.'];
        });

        $location->refresh();
        $this->assertSame('Cozy cafe.', $location->listing_data['description']);
        $this->assertSame('+43 1 234', $location->phone);
        $this->assertSame('https://cafe.example', $location->website_url);
    }

    public function test_push_without_changes_skips_the_api_call(): void
    {
        Http::fake();

        $location = Location::create([
            'name' => 'Downtown Cafe',
            'external_id' => '1',
            'zernio_account_id' => 'acc-1',
        ]);

        app(ListingUpdater::class)->push($location, ['description' => null, 'opening_hours' => [], 'special_hours' => []]);

        Http::assertNothingSent();
    }
}
