<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store lat/lng for own locations and competitors (from the Places API) so a
 * competitor can be auto-scoped to the own locations in its city by geographic
 * distance — no manual "which locations" choice needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('cid');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('competitors', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('competitors', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
