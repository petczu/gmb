<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named groups of the workspace's own locations, managed on the Locations page.
 * A group is selectable in the dashboard/report location filter (it expands to
 * its member locations) and lets multi-region owners slice their data by
 * cluster instead of picking locations one by one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('location_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_groups');
    }
};
