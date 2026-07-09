<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CENTRAL competitor data. Snapshots are keyed by the public Google
     * place_id and shared across all workspaces: rating/review counts of a
     * place are public data, sharing means a newly added competitor comes
     * with the history other tenants (or the admin watchlist) already
     * collected, and each place costs ONE Places API call per day no matter
     * how many workspaces track it.
     */
    public function up(): void
    {
        Schema::create('place_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('place_id', 160)->index();
            // One row per place per day (daily competitors:refresh).
            $table->date('day');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);

            $table->unique(['place_id', 'day']);
        });

        // Admin watchlist: places the platform collects snapshots for even
        // before any workspace tracks them (pre-warming the history).
        Schema::create('tracked_places', function (Blueprint $table): void {
            $table->id();
            $table->string('place_id', 160)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_places');
        Schema::dropIfExists('place_snapshots');
    }
};
