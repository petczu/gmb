<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Last error from the review sync for this location, so a failing location
     * is visible in the UI instead of sitting on "Syncing…" forever.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->text('last_sync_error')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('last_sync_error');
        });
    }
};
