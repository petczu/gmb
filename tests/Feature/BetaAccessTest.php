<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureBetaApproved;
use App\Mail\BetaApprovedMail;
use App\Mail\Templates\EmailTemplateCatalog;
use App\Models\BetaAllowlistEntry;
use App\Models\User;
use App\Services\Auth\BetaAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Private beta gate: allowlist matching, application approval (approved_at +
 * email) and the middleware redirect for pending users. The central 'mysql'
 * connection is pointed at an in-memory sqlite for the pinned models.
 */
class BetaAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('beta.enabled', true);
        config()->set('superadmin.emails', ['boss@example.com']);

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('beta_allowlist', function ($table): void {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('users', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('mysql')->dropIfExists('users');
        Schema::connection('mysql')->dropIfExists('beta_allowlist');
        parent::tearDown();
    }

    private function user(?string $approvedAt = null, string $email = 'new@example.com'): User
    {
        $user = User::create([
            'name' => 'New user',
            'email' => $email,
            'password' => 'secret-secret-secret',
        ]);

        if ($approvedAt !== null) {
            $user->forceFill(['approved_at' => $approvedAt])->save();
        }

        return $user->refresh();
    }

    public function test_allowlisted_and_super_admin_emails_skip_the_queue(): void
    {
        BetaAllowlistEntry::create(['email' => 'vip@example.com']);
        $beta = app(BetaAccess::class);

        $this->assertTrue($beta->grantsImmediateAccess('vip@example.com'));
        $this->assertTrue($beta->grantsImmediateAccess('  VIP@Example.com  '));
        $this->assertTrue($beta->grantsImmediateAccess('boss@example.com'));
        $this->assertFalse($beta->grantsImmediateAccess('stranger@example.com'));
    }

    public function test_disabled_beta_mode_lets_everyone_in(): void
    {
        config()->set('beta.enabled', false);

        $this->assertTrue(app(BetaAccess::class)->grantsImmediateAccess('stranger@example.com'));
        $this->assertTrue($this->user()->hasBetaAccess());
    }

    public function test_approve_sets_timestamp_and_sends_the_email_once(): void
    {
        Mail::fake();
        $user = $this->user();

        $this->assertFalse($user->hasBetaAccess());

        app(BetaAccess::class)->approve($user);

        $this->assertNotNull($user->refresh()->approved_at);
        $this->assertTrue($user->hasBetaAccess());
        Mail::assertSent(BetaApprovedMail::class, 1);

        // Approving again is a no-op (no duplicate email).
        app(BetaAccess::class)->approve($user->refresh());
        Mail::assertSent(BetaApprovedMail::class, 1);
    }

    public function test_middleware_redirects_pending_users_but_not_approved_ones(): void
    {
        $middleware = new EnsureBetaApproved;
        $next = fn (): Response => response('ok');

        $requestFor = function (?User $user): Request {
            $request = Request::create('/reviews');
            $request->setUserResolver(fn (): ?User => $user);

            return $request;
        };

        $this->app['router']->get('beta/pending', fn (): string => 'pending')->name('beta.pending');

        $pending = $middleware->handle($requestFor($this->user()), $next);
        $this->assertSame(302, $pending->getStatusCode());
        $this->assertStringContainsString('beta/pending', (string) $pending->headers->get('Location'));

        $approved = $middleware->handle($requestFor($this->user(now()->toDateTimeString(), 'ok@example.com')), $next);
        $this->assertSame('ok', $approved->getContent());

        $guest = $middleware->handle($requestFor(null), $next);
        $this->assertSame('ok', $guest->getContent());
    }

    public function test_beta_templates_exist_in_the_catalog(): void
    {
        foreach (['beta_received', 'beta_approved'] as $key) {
            $this->assertTrue(EmailTemplateCatalog::has($key), "missing catalog template: {$key}");
            $this->assertNotSame('', EmailTemplateCatalog::defaultBody($key, 'en'));
            $this->assertNotSame('', EmailTemplateCatalog::defaultBody($key, 'de'));
        }
    }
}
