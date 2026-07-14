<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Auth\Register;
use App\Models\Invitation;
use App\Models\Workspace;
use App\Services\Auth\EmailOtp;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Invitation-bound sign-up: when the visitor arrives from an invitation link,
 * the registration email is prefilled + locked to the invited address, and the
 * server re-forces it from the (untamperable) session token so a swapped email
 * can't quietly create a mismatched solo account instead of joining.
 */
class RegisterInvitedEmailLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->timestamps();
        });

        // Workspace is a stancl Tenant → the "tenants" table on the default
        // (central) connection, separate from the hardcoded-"mysql" models.
        Schema::create('tenants', function ($table): void {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('invitations', function ($table): void {
            $table->increments('id');
            $table->string('token')->unique();
            $table->string('workspace_id')->nullable();
            $table->string('email');
            $table->string('role')->default('member');
            $table->string('locale')->nullable();
            $table->text('location_ids')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        Schema::connection('mysql')->dropIfExists('invitations');
        Schema::dropIfExists('tenants');
        Schema::connection('mysql')->dropIfExists('users');
        parent::tearDown();
    }

    private function invitation(array $overrides = []): Invitation
    {
        // Seed the tenant row directly — Workspace::create would fire stancl's
        // database-provisioning listener, which this test doesn't need.
        Workspace::withoutEvents(fn () => Workspace::create(['id' => 'ws-1', 'name' => 'Acme Agency']));

        return Invitation::create(array_merge([
            'token' => Invitation::makeToken(),
            'workspace_id' => 'ws-1',
            'email' => 'invitee@example.com',
            'role' => 'member',
            'expires_at' => now()->addDays(14),
        ], $overrides));
    }

    public function test_the_email_is_prefilled_and_the_invite_banner_shown(): void
    {
        $invite = $this->invitation();
        session(['pending_invite' => $invite->token]);

        Livewire::test(Register::class)
            ->assertSet('data.email', 'invitee@example.com')
            ->assertSet('invitedEmail', 'invitee@example.com')
            ->assertSee('Acme Agency');
    }

    public function test_a_swapped_email_is_re_forced_from_the_session_token(): void
    {
        $this->mock(EmailOtp::class, function ($mock): void {
            $mock->shouldReceive('send')->andReturnNull();
        });

        $invite = $this->invitation();
        session(['pending_invite' => $invite->token]);

        // Client tampers the locked field, then submits step 1.
        Livewire::test(Register::class)
            ->set('data.email', 'attacker@evil.com')
            ->call('register')
            ->assertSet('data.email', 'invitee@example.com')
            ->assertSet('step', 2);
    }

    public function test_a_plain_registration_leaves_the_email_editable(): void
    {
        Livewire::test(Register::class)
            ->assertSet('invitedEmail', null)
            ->assertSet('data.email', null);
    }
}
