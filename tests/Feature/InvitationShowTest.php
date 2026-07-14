<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The public invitation landing (/invite/{token}) returns HTTP responses
 * (view + status) for the invalid/expired and wrong-account cases — the
 * controller's return type must allow those, not only View|RedirectResponse.
 */
class InvitationShowTest extends TestCase
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

        Schema::connection('mysql')->create('invitations', function ($table): void {
            $table->increments('id');
            $table->string('token')->unique();
            $table->string('workspace_id')->nullable();
            $table->string('email');
            $table->string('role')->default('member');
            $table->string('locale')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('mysql')->dropIfExists('invitations');
        Schema::connection('mysql')->dropIfExists('users');
        parent::tearDown();
    }

    private function invitation(array $overrides = []): Invitation
    {
        return Invitation::create(array_merge([
            'token' => Invitation::makeToken(),
            'workspace_id' => 'ws-1',
            'email' => 'invitee@example.com',
            'role' => 'member',
            'expires_at' => now()->addDays(14),
        ], $overrides));
    }

    public function test_a_signed_in_wrong_account_gets_a_friendly_page_not_a_type_error(): void
    {
        $other = User::create(['name' => 'Someone Else', 'email' => 'other@example.com', 'password' => 'secret-secret-1']);
        $invite = $this->invitation();

        // Not an error (no 403/500): a plain page explaining who it's for.
        // The invited address is masked — this visitor is NOT the invitee.
        $this->actingAs($other)
            ->get(route('invite.show', $invite->token))
            ->assertOk()
            ->assertViewIs('invitations.wrong-account')
            ->assertSee('i***@example.com')
            ->assertDontSee($invite->email);
    }

    public function test_an_expired_invitation_returns_a_410_page(): void
    {
        $invite = $this->invitation(['expires_at' => now()->subDay()]);

        $this->get(route('invite.show', $invite->token))->assertStatus(410);
    }

    public function test_an_unknown_token_returns_a_410_page(): void
    {
        $this->get(route('invite.show', 'no-such-token'))->assertStatus(410);
    }

    public function test_an_expired_invite_renders_in_the_language_it_was_sent_in(): void
    {
        $invite = $this->invitation(['locale' => 'de', 'expires_at' => now()->subDay()]);

        $this->get(route('invite.show', $invite->token))
            ->assertStatus(410)
            ->assertSee(__('invitations.invalid_title', [], 'de'))
            ->assertDontSee(__('invitations.invalid_title', [], 'en'));
    }
}
