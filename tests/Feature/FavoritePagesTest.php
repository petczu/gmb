<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\Posts;
use App\Filament\App\Resources\Locations\Pages\ListLocations;
use App\Livewire\FavoritePageStar;
use App\Models\User;
use App\Support\FavoritePages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sidebar favorites: the header star toggles a page in users.favorite_pages,
 * and the stored entries become extra ungrouped navigation items.
 */
class FavoritePagesTest extends TestCase
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
            $table->json('favorite_pages')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('mysql')->dropIfExists('users');
        parent::tearDown();
    }

    private function user(): User
    {
        $user = User::create(['name' => 'P', 'email' => 'fav@example.com', 'password' => 'secret-secret-1']);
        $this->actingAs($user);

        return $user;
    }

    public function test_star_component_toggles_the_favorite(): void
    {
        $user = $this->user();

        $component = Livewire::test(FavoritePageStar::class, ['path' => '/posts', 'label' => 'Posts']);
        $component->assertSet('starred', false);

        $component->call('toggle')->assertSet('starred', true);
        $this->assertSame([['path' => '/posts', 'label' => 'Posts', 'icon' => null]], $user->refresh()->favorite_pages);

        $component->call('toggle')->assertSet('starred', false);
        $this->assertSame([], $user->refresh()->favorite_pages);
    }

    public function test_favorites_become_navigation_items(): void
    {
        $user = $this->user();
        FavoritePages::toggle($user, '/posts', 'Posts');
        FavoritePages::toggle($user, '/reviews', 'All reviews');

        $items = FavoritePages::navigationItems($user->refresh());

        $this->assertCount(2, $items);
        $this->assertSame('Posts', $items[0]->getLabel());
        $this->assertStringEndsWith('/posts', (string) $items[0]->getUrl());
        $this->assertSame('All reviews', $items[1]->getLabel());
    }

    public function test_stored_bare_icon_names_are_normalized_for_the_sidebar(): void
    {
        $user = $this->user();
        $user->forceFill(['favorite_pages' => [
            ['path' => '/locations', 'label' => 'Locations', 'icon' => 'o-building-storefront'],
        ]])->save();

        $items = FavoritePages::navigationItems($user->refresh());

        $this->assertSame('heroicon-o-building-storefront', $items[0]->getIcon());
    }

    public function test_label_for_resolves_pages_and_resource_page_classes(): void
    {
        $this->assertSame(__('pages/posts.nav'), FavoritePages::labelFor(Posts::class));
        $this->assertSame('Locations', FavoritePages::labelFor(ListLocations::class));
    }
}
