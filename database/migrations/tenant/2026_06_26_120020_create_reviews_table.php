<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table (per-workspace DB): a Google review for a location, plus its
 * reply state. One reply per review (GBP rule), folded in here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('external_review_id')->unique();
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating');             // 1..5
            $table->longText('text')->nullable();
            $table->string('review_link')->nullable();
            $table->timestamp('created_at_external')->nullable();

            // Reply state
            $table->longText('reply_text')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('reply_status')->nullable();        // published | draft | failed
            $table->string('reply_source')->nullable();        // manual | ai_auto | ai_draft

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'rating']);
            $table->index('created_at_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
