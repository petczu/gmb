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

    public function test_customize_toggles_arranging_and_add_widget_restores_hidden(): void
    {
        $user = User::create(['name' => 'P', 'email' => 'p@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill([
            'approved_at' => now(),
            'dashboard_widgets' => array_values(array_diff(DashboardWidgets::KEYS, ['review_stats'])),
        ])->save();
        $this->actingAs($user);

        // Customize toggles arrange mode (no modal, no save).
        $page = Livewire::test(Dashboard::class);
        $page->callAction('customize')->assertHasNoActionErrors();
        $this->assertTrue($page->get('arranging'));

        // Add widget restores the hidden one; a full selection stores null so
        // widgets added later stay visible by default.
        $page->callAction('addWidget', data: ['widgets' => ['review_stats']])
            ->assertHasNoActionErrors();

        $this->assertNull($user->refresh()->dashboard_widgets);
        $this->assertTrue(DashboardWidgets::visible('review_stats'));

        // Toggling again leaves arrange mode.
        $page->callAction('customize')->assertHasNoActionErrors();
        $this->assertFalse($page->get('arranging'));
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
