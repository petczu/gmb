<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            // Monthly target of NEW reviews for this location. Null = no goal set;
            // such locations are skipped in goal-progress emails but still watched
            // for anomalies.
            $table->unsignedInteger('review_goal')->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('review_goal');
        });
    }
};
