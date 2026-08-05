<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The share access window is a moment, not a day: from/until carry a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_shares', function (Blueprint $table) {
            $table->dateTime('access_from')->nullable()->change();
            $table->dateTime('access_until')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('post_shares', function (Blueprint $table) {
            $table->date('access_from')->nullable()->change();
            $table->date('access_until')->nullable()->change();
        });
    }
};
