<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user profile settings: avatar, display timezone, and the first day of the
 * week (Monday/Sunday) used for calendars and weekly report buckets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->string('timezone')->nullable();
            $table->string('week_start')->default('monday'); // monday | sunday
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'timezone', 'week_start']);
        });
    }
};
