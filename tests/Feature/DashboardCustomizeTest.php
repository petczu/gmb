<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Widgets\ReviewStatsOverview;
use App\Models\User;
use App\Support\DashboardWidgets;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The dashboard "Customize" action: saving a widget selection persists it on
 * the user (null when everything is selected) and the widgets honor it.
 */
class DashboardCustomizeTest extends TestCase
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
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('dashboard_widgets')->nullable();
            $table->timestamps();
        });

        // Tenant table for the widget-poll test (default sqlite connection).
        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('reply_text')->nullable();
            $table->dateTime('created_at_external')->nullable();
            $table->timestamps();
        });

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reviews');
        Schema::connection('mysql')->dropIfExists('users');
        parent::tearDown();
    }

    public function test_customize_saves_selection_and_full_selection_stores_null(): void
    {
        $user = User::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill(['approved_at' => now()])->save();
        $this->actingAs($user);

        // Uncheck only review_stats. (callAction data index-merges over the
        // form defaults, so an unambiguous "all but one" selection is used.)
        $withoutReviewStats = array_values(array_diff(DashboardWidgets::KEYS, ['review_stats']));

        Livewire::test(Dashboard::class)
            ->callAction('customize', data: ['widgets' => $withoutReviewStats])
            ->assertHasNoActionErrors();

        $this->assertSame($withoutReviewStats, $user->refresh()->dashboard_widgets);
        $this->assertFalse(DashboardWidgets::visible('review_stats'));
        $this->assertTrue(DashboardWidgets::visible('latest_reviews'));

        // Selecting everything stores null (widgets added later stay visible).
        Livewire::test(Dashboard::class)
            ->callAction('customize', data: ['widgets' => DashboardWidgets::KEYS])
            ->assertHasNoActionErrors();

        $this->assertNull($user->refresh()->dashboard_widgets);
    }

    /**
     * A widget hidden via Customize is usually still in the DOM and polling.
     * Filament's default hydrate check would abort 403 and blow up the page —
     * SurvivesBeingHidden must let the stale poll pass quietly.
     */
    public function test_hidden_widget_survives_a_stale_poll(): void
    {
        $user = User::create(['name' => 'P', 'email' => 'p2@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill([
            'approved_at' => now(),
            'dashboard_widgets' => ['latest_reviews'], // review_stats hidden
        ])->save();
        $this->actingAs($user);

        $this->assertFalse(ReviewStatsOverview::canView());

        Livewire::test(ReviewStatsOverview::class)
            ->call('$refresh')
            ->assertOk();
    }
}
