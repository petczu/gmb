<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Resources\Locations\HoursBulkEdit;
use App\Filament\App\Resources\Locations\Pages\ListLocations;
use App\Models\ExternalCalendar;
use App\Models\Location;
use App\Models\ScheduledListingUpdate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bulk "Edit hours" on the locations table: pushes regular/special hours to
 * every selected matched location, merges holiday-calendar picks as closed
 * days, and preserves the rest of the stored listing_data copy.
 */
class BulkHoursTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reviews.zernio_base_url', 'https://zernio.test/api/v1');
        config()->set('services.reviews.zernio_key', 'test-key');

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('users', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        foreach (['permissions', 'roles'] as $name) {
            Schema::connection('mysql')->create($name, function ($table): void {
                $table->increments('id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }
        Schema::connection('mysql')->create('model_has_permissions', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('team_id')->nullable();
        });
        Schema::connection('mysql')->create('model_has_roles', function ($table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('team_id')->nullable();
        });
        Schema::connection('mysql')->create('role_has_permissions', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::connection('mysql')->create('workspace_user', function ($table): void {
            $table->unsignedBigInteger('user_id');
            $table->string('workspace_id')->nullable();
            $table->string('type')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        $user = User::create(['name' => 'P', 'email' => 'bulk@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill(['approved_at' => now()])->save();
        $this->actingAs($user);
        Gate::before(fn (): bool => true);

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->string('status')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->json('listing_data')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // The Locations table renders a "Group" filter querying this table.
        Schema::create('location_groups', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->json('location_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendars', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('url', 2048);
            $table->string('color', 20)->default('green');
            $table->boolean('enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendar_events', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('external_calendar_id')->index();
            $table->date('date')->index();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('scheduled_listing_updates', function ($table): void {
            $table->increments('id');
            $table->json('location_ids');
            $table->json('opening_hours')->nullable();
            $table->json('special_hours')->nullable();
            $table->date('apply_on')->index();
            $table->timestamp('applied_at')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        foreach (['scheduled_listing_updates', 'external_calendar_events', 'external_calendars', 'location_groups', 'locations'] as $table) {
            Schema::dropIfExists($table);
        }
        foreach (['workspace_user', 'role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions', 'users'] as $table) {
            Schema::connection('mysql')->dropIfExists($table);
        }
        parent::tearDown();
    }

    private function location(): Location
    {
        return Location::create([
            'name' => 'Vienna',
            'external_id' => 'g-1',
            'zernio_account_id' => 'acc-1',
        ]);
    }

    public function test_bulk_hours_updates_matched_locations_and_reports_unmatched(): void
    {
        Http::fake(['zernio.test/*' => Http::response(['ok' => true])]);

        $matched = Location::create([
            'name' => 'Vienna',
            'external_id' => 'g-1',
            'zernio_account_id' => 'acc-1',
            'listing_data' => ['description' => 'Keep me', 'opening_hours' => []],
        ]);
        $unmatched = Location::create(['name' => 'Draft location']);

        $calendar = ExternalCalendar::create(['name' => 'AT', 'url' => 'https://calendar.test/at.ics']);
        $holiday = $calendar->events()->create(['date' => now()->addDays(10)->toDateString(), 'title' => 'Staatsfeiertag']);

        Livewire::test(ListLocations::class)->callAction('editHours', [
            'locations' => [$matched->id, $unmatched->id],
            'apply_regular' => true,
            'opening_hours' => [
                ['day' => 'MONDAY', 'open' => '09:00', 'close' => '18:00'],
                ['day' => 'SATURDAY', 'open' => '10:00', 'close' => '14:00'],
            ],
            'apply_special' => true,
            'holiday_events' => [$holiday->id],
            'special_hours' => [
                ['start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(3)->toDateString(), 'closed' => true],
            ],
        ]);

        // One PATCH for the matched location only, carrying both hour sets.
        // (The passed rows are merged with the repeater's Mon-Fri defaults,
        // exactly like a user editing the prefilled form.)
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($holiday): bool {
            $periods = $request['regularHours']['periods'] ?? [];
            $special = $request['specialHours']['specialHourPeriods'] ?? [];
            $saturday = collect($periods)->firstWhere('openDay', 'SATURDAY');

            return str_contains($request->url(), 'accounts/acc-1/gmb-location-details')
                && str_contains((string) $request['updateMask'], 'regularHours')
                && str_contains((string) $request['updateMask'], 'specialHours')
                && ($saturday['openTime'] ?? null) === '10:00'
                && count($special) === 2
                && $special[1]['startDate']['day'] === $holiday->date->day
                && $special[1]['closed'] === true;
        });

        // The stored copy gains the hours but keeps unrelated fields.
        $matched->refresh();
        $this->assertSame('Keep me', $matched->listing_data['description']);
        $this->assertNotEmpty($matched->listing_data['opening_hours']);
        $this->assertCount(2, $matched->listing_data['special_hours']);

        $this->assertNull($unmatched->refresh()->listing_data);
    }

    public function test_nothing_is_pushed_when_no_section_is_enabled(): void
    {
        Http::fake();

        $location = Location::create(['name' => 'Vienna', 'external_id' => 'g-1', 'zernio_account_id' => 'acc-1']);

        Livewire::test(ListLocations::class)->callAction('editHours', [
            'locations' => [$location->id],
            'apply_regular' => false,
            'apply_special' => false,
            'opening_hours' => [],
            'special_hours' => [],
        ]);

        Http::assertNothingSent();
    }

    public function test_a_future_apply_date_parks_the_update_instead_of_pushing(): void
    {
        Http::fake();
        $location = $this->location();

        Livewire::test(ListLocations::class)->callAction('editHours', [
            'locations' => [$location->id],
            'apply_regular' => true,
            'opening_hours' => [
                ['day' => 'MONDAY', 'open' => '10:00', 'close' => '17:00'],
            ],
            'apply_special' => false,
            'special_hours' => [],
            'apply_on' => now()->addMonths(2)->toDateString(),
        ]);

        Http::assertNothingSent();

        $update = ScheduledListingUpdate::query()->sole();
        $this->assertNull($update->applied_at);
        $this->assertSame(now()->addMonths(2)->toDateString(), $update->apply_on->toDateString());
        $this->assertContains($location->id, $update->location_ids);
    }

    public function test_the_command_applies_due_updates_and_skips_future_ones(): void
    {
        Http::fake(['zernio.test/*' => Http::response(['ok' => true])]);
        $location = $this->location();

        $due = ScheduledListingUpdate::create([
            'location_ids' => [$location->id],
            'opening_hours' => [['day' => 'MONDAY', 'open' => '10:00', 'close' => '17:00']],
            'apply_on' => now()->toDateString(),
        ]);
        $future = ScheduledListingUpdate::create([
            'location_ids' => [$location->id],
            'opening_hours' => [['day' => 'TUESDAY', 'open' => '10:00', 'close' => '17:00']],
            'apply_on' => now()->addMonth()->toDateString(),
        ]);

        [$updated, $failed] = HoursBulkEdit::push(
            Location::query()->whereIn('id', $due->location_ids)->get(),
            $due->opening_hours,
            $due->special_hours,
        );
        $due->forceFill(['applied_at' => now()])->save();

        $this->assertSame(1, $updated);
        $this->assertSame([], $failed);
        Http::assertSentCount(1);
        $this->assertNotNull($due->refresh()->applied_at);
        $this->assertNull($future->refresh()->applied_at);
    }
}
