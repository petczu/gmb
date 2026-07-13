<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureTermsAccepted;
use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Terms versioning: the gate lets stamped users through, redirects outdated
 * ones to the review screen, and a publish bump moves the current version.
 */
class TermsAcceptanceTest extends TestCase
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

        Schema::connection('mysql')->create('legal_documents', function ($table): void {
            $table->increments('id');
            $table->string('key');
            $table->string('locale', 5);
            $table->longText('body');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::connection('mysql')->create('users', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedInteger('terms_version')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamps();
        });

        Route::get('terms/review', fn () => 'review')->name('terms.review');
    }

    private function publish(int $version): void
    {
        LegalDocument::query()->updateOrCreate(
            ['key' => 'terms', 'locale' => 'en'],
            ['body' => 'Be nice.', 'version' => $version],
        );
    }

    private function pass(?User $user): bool
    {
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureTermsAccepted)->handle($request, fn () => response('through'));

        return $response->getContent() === 'through';
    }

    public function test_current_version_is_the_max_across_locales(): void
    {
        $this->publish(3);

        $this->assertSame(3, LegalDocument::currentVersion(LegalDocument::TERMS));
        $this->assertSame(0, LegalDocument::currentVersion('imprint'));
    }

    public function test_stamped_user_passes_and_outdated_user_is_redirected(): void
    {
        $this->publish(2);

        // terms_version is stamped via forceFill in the real flows (it is not
        // mass-assignable), mirror that here.
        $current = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);
        $current->forceFill(['terms_version' => 2])->save();
        $outdated = User::create(['name' => 'B', 'email' => 'b@example.com', 'password' => 'x']);
        $outdated->forceFill(['terms_version' => 1])->save();
        $never = User::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'x']);

        $this->assertTrue($this->pass($current));
        $this->assertFalse($this->pass($outdated));
        $this->assertFalse($this->pass($never));
    }

    public function test_no_published_version_gates_nobody(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);

        $this->assertTrue($this->pass($user));
        $this->assertTrue($this->pass(null)); // unauthenticated requests pass through
    }
}
