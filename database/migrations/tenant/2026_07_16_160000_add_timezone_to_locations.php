<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-location IANA timezone (e.g. "Asia/Dubai"). Auto-detected from Google on
 * connect and editable by hand. Auto-reply working hours are interpreted in the
 * location's own timezone, so a multi-city workspace schedules each reply in the
 * right local time instead of one workspace-wide (server) timezone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('timezone', 64)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
