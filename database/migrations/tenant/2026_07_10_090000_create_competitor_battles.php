<?php

declare(strict_types=1);

use App\Models\Competitor;
use App\Models\CompetitorBattle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Competitor benchmark moves from 1-vs-1 (one own location vs one place) to
 * named "battles": a group of the workspace's own locations compared against a
 * group of competitor places. Each competitor row becomes a place inside a
 * battle; existing competitors are migrated to one auto-named battle each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_battles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            // The workspace's own location ids on this side of the battle.
            $table->json('own_location_ids')->nullable();
            $table->timestamps();
        });

        Schema::table('competitors', function (Blueprint $table): void {
            $table->unsignedBigInteger('battle_id')->nullable()->after('id')->index();
            // location_id is no longer required (a battle can span locations).
            $table->unsignedBigInteger('location_id')->nullable()->change();
        });

        // Backfill: one battle per existing competitor, keeping its own location.
        Competitor::query()->whereNull('battle_id')->get()->each(function (Competitor $competitor): void {
            $battle = CompetitorBattle::create([
                'name' => $competitor->name,
                'own_location_ids' => $competitor->location_id ? [(int) $competitor->location_id] : [],
            ]);

            $competitor->forceFill(['battle_id' => $battle->id])->save();
        });
    }

    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table): void {
            $table->dropColumn('battle_id');
        });

        Schema::dropIfExists('competitor_battles');
    }
};
