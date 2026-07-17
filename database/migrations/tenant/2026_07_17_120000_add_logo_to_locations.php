<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-location logo (uploaded to the 'uploads' disk). Used on the Google post
 * preview card; falls back to the workspace logo when a location has none, so a
 * multi-brand workspace can show each location's own mark.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('logo_path');
        });
    }
};
