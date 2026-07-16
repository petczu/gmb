<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\Competitors;
use App\Models\Competitor;
use App\Models\CompetitorBattle;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Grouping on the competitor benchmark: combining tracked competitors into one
 * named battle (the chart's group line) and moving one back out again. Tenant
 * tables live on the default sqlite connection; the page methods are invoked
 * directly.
 */
class CompetitorGroupsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('competitor_battles', function ($table): void {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->json('own_location_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('competitors', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('battle_id')->nullable();
            $table->string('place_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name');
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('competitors');
        Schema::dropIfExists('competitor_battles');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    private function trackedCompetitor(string $name, string $placeId): Competitor
    {
        $battle = CompetitorBattle::create(['own_location_ids' => [1]]);

        return Competitor::create([
            'battle_id' => $battle->id,
            'place_id' => $placeId,
            'name' => $name,
            'reviews_count' => 100,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function createCompetitorGroup(array $data): void
    {
        $method = new ReflectionMethod(Competitors::class, 'createGroup');
        $method->invoke(new Competitors, $data);
    }

    public function test_grouping_competitors_moves_them_into_one_named_battle(): void
    {
        $a = $this->trackedCompetitor('Pizza North', 'p-a');
        $b = $this->trackedCompetitor('Pizza South', 'p-b');

        $this->createCompetitorGroup(['name' => 'Pizza chains', 'competitor_ids' => [$a->id, $b->id]]);

        // The two single battles collapse into one named group battle.
        $this->assertSame(1, CompetitorBattle::count());
        $group = CompetitorBattle::sole();
        $this->assertSame('Pizza chains', $group->name);
        $this->assertSame(2, $group->competitors()->count());
    }

    public function test_ungrouping_moves_a_competitor_back_to_its_own_battle(): void
    {
        $a = $this->trackedCompetitor('Pizza North', 'p-a');
        $b = $this->trackedCompetitor('Pizza South', 'p-b');
        $this->createCompetitorGroup(['name' => 'Pizza chains', 'competitor_ids' => [$a->id, $b->id]]);

        $method = new ReflectionMethod(Competitors::class, 'ungroup');
        $method->invoke(new Competitors, $a->fresh());

        // Both competitors still exist; the group of one dissolves back to a
        // plain (unnamed) competitor.
        $this->assertSame(2, Competitor::count());
        $this->assertNull($a->fresh()->battle->name);
        $this->assertNull($b->fresh()->battle->name);
    }

    public function test_grouping_needs_at_least_two_competitors(): void
    {
        $a = $this->trackedCompetitor('Solo', 'p-solo');

        $this->createCompetitorGroup(['name' => 'Nope', 'competitor_ids' => [$a->id]]);

        // No named group is created; the original single battle is untouched.
        $this->assertSame(1, CompetitorBattle::count());
        $this->assertNull(CompetitorBattle::sole()->name);
    }
}
