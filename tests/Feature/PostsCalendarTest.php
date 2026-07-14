<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\Posts;
use App\Models\ExternalCalendar;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostNote;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The posts calendar page: sticky notes (add, edit, color, tag, delete),
 * external calendar toggles, and the draft flow (save without publishing,
 * then publish the draft later).
 */
class PostsCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.reviews.zernio_base_url', 'https://zernio.test/api/v1');
        config()->set('services.reviews.zernio_key', 'test-key');

        // The page is gated on publish_posts; grant it wholesale here.
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
            $table->timestamps();
        });

        // Spatie's gate hook queries these on every can(); empty tables suffice
        // because the Gate::before below grants everything anyway.
        Schema::connection('mysql')->create('permissions', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::connection('mysql')->create('roles', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::connection('mysql')->create('model_has_permissions', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('team_id')->nullable();
        });
        Schema::connection('mysql')->create('model_has_roles', function ($table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('team_id')->nullable();
        });
        Schema::connection('mysql')->create('role_has_permissions', function ($table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        $user = User::create(['name' => 'P', 'email' => 'posts@example.com', 'password' => 'secret-secret-1']);
        $user->forceFill(['approved_at' => now()])->save();
        $this->actingAs($user);

        Gate::before(fn (): bool => true);

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function ($table): void {
            $table->increments('id');
            $table->string('type', 20);
            $table->text('caption')->nullable();
            $table->string('title')->nullable();
            $table->string('cta_type', 20)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('photo_category', 30)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('voucher_code')->nullable();
            $table->string('redeem_url', 2048)->nullable();
            $table->string('terms_url', 2048)->nullable();
            $table->json('location_ids');
            $table->json('source_ids');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20);
            $table->json('external_ids')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });

        Schema::create('post_notes', function ($table): void {
            $table->increments('id');
            $table->date('date')->index();
            $table->text('body')->nullable();
            $table->string('color', 20)->default('yellow');
            $table->string('tag', 60)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendars', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('url', 2048);
            $table->string('color', 20)->default('green');
            $table->boolean('enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendar_events', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('external_calendar_id')->index();
            $table->date('date')->index();
            $table->string('title');
            $table->timestamps();
        });

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        foreach (['external_calendar_events', 'external_calendars', 'post_notes', 'posts', 'locations'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function location(): Location
    {
        return Location::create([
            'external_id' => '118530',
            'zernio_account_id' => 'acc-1',
            'name' => 'Downtown Cafe',
        ]);
    }

    public function test_notes_can_be_added_edited_and_deleted(): void
    {
        $component = Livewire::test(Posts::class);

        $component->call('addNote', now()->toDateString());
        $note = PostNote::query()->sole();
        $this->assertSame('yellow', $note->color);

        $component->call('updateNote', $note->id, 'body', 'Launch teaser here');
        $component->call('updateNote', $note->id, 'color', 'teal');
        $component->call('updateNote', $note->id, 'tag', 'campaign');

        $note->refresh();
        $this->assertSame('Launch teaser here', $note->body);
        $this->assertSame('teal', $note->color);
        $this->assertSame('campaign', $note->tag);

        // Unknown colors and fields are ignored.
        $component->call('updateNote', $note->id, 'color', 'neon');
        $component->call('updateNote', $note->id, 'date', '2000-01-01');
        $this->assertSame('teal', $note->refresh()->color);

        $component->call('deleteNote', $note->id);
        $this->assertSame(0, PostNote::query()->count());
    }

    public function test_note_tags_are_offered_for_reuse(): void
    {
        PostNote::create(['date' => now()->toDateString(), 'tag' => 'campaign']);
        PostNote::create(['date' => now()->toDateString(), 'tag' => 'campaign']);
        PostNote::create(['date' => now()->toDateString(), 'tag' => 'holiday']);

        $component = Livewire::test(Posts::class);

        $this->assertSame(['campaign', 'holiday'], $component->instance()->noteTags());
    }

    public function test_notes_can_be_filtered_by_tag(): void
    {
        PostNote::create(['date' => now()->toDateString(), 'tag' => 'campaign', 'body' => 'A']);
        PostNote::create(['date' => now()->toDateString(), 'tag' => 'holiday', 'body' => 'B']);
        PostNote::create(['date' => now()->toDateString(), 'body' => 'C']);

        $component = Livewire::test(Posts::class);

        $notesToday = fn (): array => collect($component->instance()->calendarWeeks())
            ->flatten(1)
            ->firstWhere(fn (array $day): bool => $day['date']->isToday())['notes']
            ->pluck('body')
            ->all();

        $this->assertSame(['A', 'B', 'C'], $notesToday());

        $component->call('toggleNoteTagFilter', 'campaign');
        $component->call('toggleNoteTagFilter', Posts::UNTAGGED);
        $this->assertSame(['B'], $notesToday());

        // Re-enabling brings the tag back, and the choice is session-persisted.
        $component->call('toggleNoteTagFilter', 'campaign');
        $this->assertSame(['A', 'B'], $notesToday());
        $this->assertSame([Posts::UNTAGGED], session('posts_hidden_note_tags'));
    }

    public function test_external_calendar_can_be_toggled_and_deleted(): void
    {
        $calendar = ExternalCalendar::create(['name' => 'AT Holidays', 'url' => 'https://calendar.test/at.ics']);

        $component = Livewire::test(Posts::class);

        $component->call('toggleCalendar', $calendar->id);
        $this->assertFalse($calendar->refresh()->enabled);

        $component->call('deleteCalendar', $calendar->id);
        $this->assertSame(0, ExternalCalendar::query()->count());
    }

    public function test_save_draft_stores_the_post_without_publishing(): void
    {
        Http::fake();
        $location = $this->location();

        Livewire::test(Posts::class)->callAction('create', [
            'type' => 'update',
            'locations' => [$location->id],
            'caption' => 'Coming soon…',
        ], arguments: ['draft' => true]);

        $post = Post::query()->sole();
        $this->assertSame('draft', $post->status);
        Http::assertNothingSent();
    }

    public function test_a_draft_can_be_published_later(): void
    {
        Http::fake([
            'zernio.test/api/v1/posts' => Http::response(['_id' => 'zp-9', 'status' => 'published'], 201),
        ]);
        $location = $this->location();

        $draft = Post::create([
            'type' => 'update',
            'caption' => 'Coming soon…',
            'location_ids' => [$location->id],
            'source_ids' => [$location->external_id],
            'status' => 'draft',
        ]);

        $component = Livewire::test(Posts::class);
        $component->set('viewingPostId', $draft->id);
        $component->callAction('editDraft', [
            'type' => 'update',
            'locations' => [$location->id],
            'caption' => 'We are live!',
        ]);

        $draft->refresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame('We are live!', $draft->caption);
        Http::assertSentCount(1);
    }
}
