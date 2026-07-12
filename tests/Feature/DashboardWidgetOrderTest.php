<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\Dashboard;
use App\Models\User;
use App\Support\DashboardWidgets;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drag-and-drop widget reordering: reorderWidgets() persists the dragged
 * (visible) order and appends the remaining widgets; order() merges saved
 * preferences with defaults so widgets added later still get a position.
 */
class DashboardWidgetOrderTest extends TestCase
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
            $table->json('dashboard_widget_order')->nullable();
            $table->json('dashboard_widget_spans')->nullable();
            $table->timestamps();
        });

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

    private function user(): User
    {
        $user = User::create(['name' => 'P', 'email' => 'order@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill(['approved_at' => now()])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_reorder_persists_dragged_keys_and_appends_the_rest(): void
    {
        $user = $this->user();

        Livewire::test(Dashboard::class)
            ->call('reorderWidgets', ['latest_reviews', 'review_stats', 'rating_trend'])
            ->assertOk();

        $saved = $user->refresh()->dashboard_widget_order;

        $this->assertSame(['latest_reviews', 'review_stats', 'rating_trend'], array_slice($saved, 0, 3));
        // Every known widget keeps a position; nothing is lost.
        $this->assertEqualsCanonicalizing(array_keys(DashboardWidgets::classes()), $saved);

        // order() serves the saved arrangement back.
        $this->assertSame($saved, DashboardWidgets::order());
    }

    public function test_unknown_keys_are_ignored_and_empty_payload_is_a_noop(): void
    {
        $user = $this->user();

        Livewire::test(Dashboard::class)
            ->call('reorderWidgets', ['bogus', 'latest_reviews'])
            ->assertOk();

        $this->assertSame('latest_reviews', $user->refresh()->dashboard_widget_order[0]);

        Livewire::test(Dashboard::class)
            ->call('reorderWidgets', ['nothing-real'])
            ->assertOk();

        // Unchanged: the previous order survives a bogus payload.
        $this->assertSame('latest_reviews', $user->refresh()->dashboard_widget_order[0]);
    }

    public function test_order_appends_defaults_for_widgets_missing_from_the_saved_preference(): void
    {
        $user = $this->user();
        $user->forceFill(['dashboard_widget_order' => ['searches']])->save();

        $order = DashboardWidgets::order();

        $this->assertSame('searches', $order[0]);
        $this->assertEqualsCanonicalizing(array_keys(DashboardWidgets::classes()), $order);
    }

    public function test_hide_widget_removes_it_from_the_visible_selection(): void
    {
        $user = $this->user();

        Livewire::test(Dashboard::class)
            ->call('hideWidget', 'competitors_chart')
            ->assertOk();

        $saved = $user->refresh()->dashboard_widgets;

        $this->assertNotContains('competitors_chart', $saved);
        $this->assertContains('review_stats', $saved);
        $this->assertFalse(DashboardWidgets::visible('competitors_chart'));

        // Unknown keys are ignored.
        Livewire::test(Dashboard::class)->call('hideWidget', 'bogus')->assertOk();
        $this->assertSame($saved, $user->refresh()->dashboard_widgets);
    }

    public function test_toggle_widget_span_flips_between_full_and_half(): void
    {
        $user = $this->user();

        // competitors_chart defaults to full → first toggle makes it half.
        Livewire::test(Dashboard::class)->call('toggleWidgetSpan', 'competitors_chart')->assertOk();
        $this->assertSame(1, $user->refresh()->dashboard_widget_spans['competitors_chart']);
        $this->assertSame(1, DashboardWidgets::spanOverride('competitors_chart'));

        // Second toggle goes back to full.
        Livewire::test(Dashboard::class)->call('toggleWidgetSpan', 'competitors_chart')->assertOk();
        $this->assertSame('full', $user->refresh()->dashboard_widget_spans['competitors_chart']);

        // Unknown keys are ignored.
        Livewire::test(Dashboard::class)->call('toggleWidgetSpan', 'bogus')->assertOk();
        $this->assertArrayNotHasKey('bogus', $user->refresh()->dashboard_widget_spans);
    }
}
