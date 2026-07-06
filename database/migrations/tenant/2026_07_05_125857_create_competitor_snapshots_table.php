<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('competitor_id')->index();
            // One row per competitor per day (daily competitors:refresh).
            $table->date('day');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);

            $table->unique(['competitor_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_snapshots');
    }
};
