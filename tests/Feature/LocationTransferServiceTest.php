<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use App\Services\Locations\LocationTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Moving a location carries across everything tied EXCLUSIVELY to it and leaves
 * shared objects behind, remapping foreign keys onto the new rows. The tenancy
 * switch is stubbed so both "workspaces" resolve to the one test connection.
 */
class LocationTransferServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $t = fn (string $name, callable $cols) => Schema::create($name, function ($table) use ($cols): void {
            $table->increments('id');
            $cols($table);
            $table->timestamps();
        });

        $t('locations', fn ($table) => [$table->string('name'), $table->string('zernio_account_id')->nullable()]);
        $t('reviews', fn ($table) => [$table->integer('location_id'), $table->string('external_review_id')->nullable(), $table->string('reply_source')->nullable(), $table->integer('ai_agent_id')->nullable()]);
        $t('auto_reply_queue_items', fn ($table) => [$table->integer('review_id'), $table->integer('ai_agent_id')->nullable(), $table->string('status')->nullable()]);
        $t('posts', fn ($table) => [$table->json('location_ids')->nullable(), $table->string('caption')->nullable()]);
        $t('auto_reply_rules', fn ($table) => [$table->integer('location_id')]);
        $t('scheduled_listing_updates', fn ($table) => [$table->integer('location_id')]);
        $t('automations', fn ($table) => [$table->string('name'), $table->boolean('all_locations')->default(false), $table->json('location_ids')->nullable(), $table->integer('ai_agent_id')->nullable()]);
        $t('report_schedules', fn ($table) => [$table->string('name'), $table->integer('location_id')->nullable(), $table->json('location_ids')->nullable()]);
        $t('ai_agents', fn ($table) => [$table->string('name')]);
        $t('location_groups', fn ($table) => [$table->string('name'), $table->json('location_ids')->nullable()]);

        tenancy()->initialized = true;
    }

    protected function tearDown(): void
    {
        tenancy()->initialized = false;
        foreach (['locations', 'reviews', 'auto_reply_queue_items', 'posts', 'auto_reply_rules', 'scheduled_listing_updates', 'automations', 'report_schedules', 'ai_agents', 'location_groups'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function service(): LocationTransferService
    {
        $billing = $this->createMock(LocationBilling::class);

        return new class($billing) extends LocationTransferService
        {
            protected function activateTenant(Workspace $workspace): void {}

            protected function restoreTenant(?Workspace $previous): void {}
        };
    }

    public function test_it_moves_exclusive_data_and_leaves_shared_behind(): void
    {
        // Location 1 moves; location 9 is another location in the same workspace.
        DB::table('locations')->insert([['id' => 1, 'name' => 'Moving', 'zernio_account_id' => 'acc-src'], ['id' => 9, 'name' => 'Other', 'zernio_account_id' => null]]);
        DB::table('ai_agents')->insert(['id' => 5, 'name' => 'Friendly']);

        DB::table('reviews')->insert(['id' => 100, 'location_id' => 1, 'external_review_id' => 'g-1', 'reply_source' => 'ai_draft', 'ai_agent_id' => 5]);
        DB::table('auto_reply_queue_items')->insert(['id' => 200, 'review_id' => 100, 'ai_agent_id' => 5, 'status' => 'published']);

        DB::table('posts')->insert([
            ['id' => 300, 'location_ids' => json_encode([1]), 'caption' => 'exclusive'],
            ['id' => 301, 'location_ids' => json_encode([1, 9]), 'caption' => 'shared'],
        ]);
        DB::table('auto_reply_rules')->insert(['id' => 400, 'location_id' => 1]);
        DB::table('scheduled_listing_updates')->insert(['id' => 500, 'location_id' => 1]);
        DB::table('automations')->insert([
            ['id' => 600, 'name' => 'excl', 'all_locations' => false, 'location_ids' => json_encode([1]), 'ai_agent_id' => 5],
            ['id' => 601, 'name' => 'allloc', 'all_locations' => true, 'location_ids' => null, 'ai_agent_id' => 5],
        ]);
        DB::table('report_schedules')->insert(['id' => 700, 'name' => 'r', 'location_id' => 1, 'location_ids' => json_encode([1])]);
        DB::table('location_groups')->insert(['id' => 800, 'name' => 'grp', 'location_ids' => json_encode([1, 9])]);

        $src = tap(new Workspace, fn ($w) => $w->id = 'src');
        $dst = tap(new Workspace, fn ($w) => $w->id = 'dst');

        $preview = $this->service()->preview(1, $src);
        $this->assertSame(1, $preview['reviews']);
        $this->assertSame(1, $preview['posts']); // only the exclusive one
        $this->assertSame(1, $preview['automations']); // not the all_locations one
        $this->assertSame(1, $preview['agents']);

        $this->service()->transfer(1, $src, $dst);

        // Old location gone, a fresh copy exists (disconnected), other location intact.
        $this->assertDatabaseMissing('locations', ['id' => 1]);
        $newLoc = DB::table('locations')->where('name', 'Moving')->first();
        $this->assertNotNull($newLoc);
        $this->assertNull($newLoc->zernio_account_id);
        $this->assertDatabaseHas('locations', ['id' => 9]);

        // Review + queue item remapped onto the new location and a copied agent.
        $review = DB::table('reviews')->where('external_review_id', 'g-1')->first();
        $this->assertSame((int) $newLoc->id, (int) $review->location_id);
        $newAgent = DB::table('ai_agents')->where('name', 'Friendly')->where('id', '!=', 5)->first();
        $this->assertNotNull($newAgent);
        $this->assertSame((int) $newAgent->id, (int) $review->ai_agent_id);
        $this->assertSame((int) $review->id, (int) DB::table('auto_reply_queue_items')->value('review_id'));

        // Exclusive post retargeted; shared post untouched.
        $this->assertSame([(int) $newLoc->id], json_decode((string) DB::table('posts')->where('caption', 'exclusive')->value('location_ids'), true));
        $this->assertSame([1, 9], json_decode((string) DB::table('posts')->where('caption', 'shared')->value('location_ids'), true));

        // Exclusive automation moved + agent remapped; all_locations one stays.
        $excl = DB::table('automations')->where('name', 'excl')->first();
        $this->assertSame([(int) $newLoc->id], json_decode((string) $excl->location_ids, true));
        $this->assertSame((int) $newAgent->id, (int) $excl->ai_agent_id);
        $this->assertSame(5, (int) DB::table('automations')->where('name', 'allloc')->value('ai_agent_id'));

        // Rule, scheduled update and report schedule moved.
        $this->assertSame((int) $newLoc->id, (int) DB::table('auto_reply_rules')->value('location_id'));
        $this->assertSame((int) $newLoc->id, (int) DB::table('report_schedules')->value('location_id'));

        // The location was dropped from its group (group kept for location 9).
        $this->assertSame([9], json_decode((string) DB::table('location_groups')->value('location_ids'), true));
    }
}
