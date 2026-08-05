<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PostShare;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The public shared-post link: snapshot HTML with an optional password gate
 * and access window, mirroring shared reports.
 */
class PostSharePublicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // PostShare is pinned to the central mysql connection; point it at
        // sqlite for the test and create just the table it needs.
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('post_shares', function ($table): void {
            $table->increments('id');
            $table->string('token', 64)->unique();
            $table->string('workspace_id');
            $table->unsignedBigInteger('post_id');
            $table->string('title')->nullable();
            $table->text('html');
            $table->string('password')->nullable();
            $table->date('access_from')->nullable();
            $table->date('access_until')->nullable();
            $table->timestamps();
        });
    }

    private function share(array $attributes = []): PostShare
    {
        return PostShare::create($attributes + [
            'token' => 'tok-'.uniqid(),
            'workspace_id' => 'ws-1',
            'post_id' => 1,
            'title' => 'August update',
            'html' => '<!DOCTYPE html><html><body>Shared post body</body></html>',
        ]);
    }

    public function test_an_open_share_renders_the_snapshot(): void
    {
        $share = $this->share();

        $this->get(route('posts.shared', $share->token))
            ->assertOk()
            ->assertSee('Shared post body');
    }

    public function test_a_card_snapshot_gets_the_branded_page(): void
    {
        // Card-fragment snapshots (the current format) are wrapped in the
        // branded page with the platform attribution; guest comments stay
        // hidden because the workspace can't be initialized here.
        $share = $this->share(['html' => '<div class="card">Card body</div>']);

        $response = $this->get(route('posts.shared', $share->token));

        $response->assertOk()
            ->assertSee('Card body', escape: false)
            ->assertSee('Shared via', escape: false)
            ->assertDontSee('Leave your feedback');
    }

    public function test_guest_comment_endpoint_requires_the_gates(): void
    {
        $locked = $this->share([
            'html' => '<div>Card</div>',
            'password' => Hash::make('secret-1'),
        ]);

        // Locked share: commenting is forbidden until unlocked.
        $this->post(route('posts.shared.comment', $locked->token), ['name' => 'Guest', 'body' => 'Hi'])
            ->assertForbidden();

        // Open share but the workspace is unreachable in this environment:
        // the guest is sent back with a friendly error, nothing crashes.
        $open = $this->share(['html' => '<div>Card</div>']);
        $this->post(route('posts.shared.comment', $open->token), ['name' => 'Guest', 'body' => 'Hi'])
            ->assertRedirect(route('posts.shared', $open->token));
    }

    public function test_an_unknown_token_is_404(): void
    {
        $this->get(route('posts.shared', 'nope'))->assertNotFound();
    }

    public function test_outside_the_access_window_is_blocked(): void
    {
        $expired = $this->share(['access_until' => now()->subDay()->toDateString()]);
        $notYet = $this->share(['access_from' => now()->addDay()->toDateString()]);

        $this->get(route('posts.shared', $expired->token))->assertForbidden();
        $this->get(route('posts.shared', $notYet->token))->assertForbidden();
    }

    public function test_a_password_gate_unlocks_for_the_session(): void
    {
        $share = $this->share(['password' => Hash::make('secret-1')]);

        // Prompt, not content.
        $this->get(route('posts.shared', $share->token))
            ->assertOk()
            ->assertDontSee('Shared post body')
            ->assertSee('password', escape: false);

        // Wrong password stays locked.
        $this->post(route('posts.shared.unlock', $share->token), ['password' => 'wrong'])
            ->assertStatus(401);

        // Correct password unlocks and the content renders.
        $this->post(route('posts.shared.unlock', $share->token), ['password' => 'secret-1'])
            ->assertRedirect(route('posts.shared', $share->token));
        $this->get(route('posts.shared', $share->token))->assertSee('Shared post body');
    }
}
