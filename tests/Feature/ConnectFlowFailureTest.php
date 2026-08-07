<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\ConnectSelectLocation;
use App\Models\User;
use App\Services\Reviews\ZernioConnectionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Third-party failure UX for the Google/Zernio connect flow: the user always
 * gets a friendly message with a way to try again (never raw API JSON), and
 * every real upstream failure is reported to Sentry. A user cancelling the
 * Google consent is NOT an incident and must not be reported.
 */
class ConnectFlowFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
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

        Schema::create('tenants', function ($table): void {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });
        Schema::create('workspace_user', function ($table): void {
            $table->increments('id');
            $table->string('workspace_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->string('membership_type')->nullable();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
        DB::table('tenants')->insert(['id' => 'ws-1', 'name' => 'Main WS', 'created_at' => now(), 'updated_at' => now()]);

        // The select-location page lists already-connected locations.
        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        $user = User::create(['name' => 'P', 'email' => 'connect@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill(['approved_at' => now()])->save();
        DB::table('workspace_user')->insert(['workspace_id' => 'ws-1', 'user_id' => $user->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($user);

        session(['current_workspace_id' => 'ws-1']);
    }

    private function managerFailingWith(string $method, \Throwable $e): void
    {
        $manager = $this->createMock(ZernioConnectionManager::class);
        $manager->method($method)->willThrowException($e);
        $this->app->instance(ZernioConnectionManager::class, $manager);
    }

    public function test_a_failed_connect_start_reports_to_sentry_and_redirects_with_a_retry_toast(): void
    {
        Exceptions::fake();
        $this->managerFailingWith('connectUrl', new \RuntimeException('Zernio 502'));

        $response = $this->get(route('zernio.google.connect'));

        $response->assertRedirect();
        Exceptions::assertReported(fn (\RuntimeException $e): bool => $e->getMessage() === 'Zernio 502');

        // A persistent Filament toast with the friendly copy is flashed.
        $notifications = json_encode(session('filament.notifications') ?? []);
        $this->assertStringContainsString('Could not start Google connection', $notifications);
        $this->assertStringContainsString('Try again', $notifications);
        $this->assertStringNotContainsString('Zernio 502', $notifications);
    }

    public function test_a_cancelled_google_authorization_is_not_reported(): void
    {
        Exceptions::fake();

        $response = $this->get(route('zernio.google.callback', ['error' => 'access_denied']));

        $response->assertRedirect();
        Exceptions::assertNothingReported();

        $notifications = json_encode(session('filament.notifications') ?? []);
        $this->assertStringContainsString('cancelled', $notifications);
    }

    public function test_an_upstream_oauth_error_is_reported(): void
    {
        Exceptions::fake();

        $response = $this->get(route('zernio.google.callback', ['error' => 'server_error']));

        $response->assertRedirect();
        Exceptions::assertReported(fn (\RuntimeException $e): bool => str_contains($e->getMessage(), 'server_error'));
    }

    public function test_a_failed_location_listing_shows_friendly_copy_and_reports(): void
    {
        Exceptions::fake();
        $this->managerFailingWith('pendingLocations', new \RuntimeException('{"api":"raw json blob"}'));

        session(['zernio_pending' => ['profileId' => 'p1', 'pendingDataToken' => 'tok', 'tempToken' => null, 'connectToken' => null]]);

        $component = Livewire::test(ConnectSelectLocation::class);

        // Friendly copy, never the raw API payload.
        $this->assertSame(__('onboarding.could_not_load_body'), $component->get('error'));
        Exceptions::assertReported(fn (\RuntimeException $e): bool => str_contains($e->getMessage(), 'raw json blob'));
    }

    public function test_an_expired_pending_token_is_not_reported(): void
    {
        Exceptions::fake();
        $this->managerFailingWith('pendingLocations', new \RuntimeException('Pending OAuth data not found'));

        session(['zernio_pending' => ['profileId' => 'p1', 'pendingDataToken' => 'tok', 'tempToken' => null, 'connectToken' => null]]);

        $component = Livewire::test(ConnectSelectLocation::class);

        $this->assertTrue($component->get('pendingExpired'));
        Exceptions::assertNothingReported();
    }
}
