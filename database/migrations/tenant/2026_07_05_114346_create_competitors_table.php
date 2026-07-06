<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitors', function (Blueprint $table): void {
            $table->id();
            // The own location this competitor is compared against.
            $table->unsignedBigInteger('location_id')->index();
            $table->string('place_id'); // Google Places id
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'place_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitors');
    }
};
