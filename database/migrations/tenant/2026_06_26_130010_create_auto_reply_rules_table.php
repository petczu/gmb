<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: one row per star group (1..5). A workspace-wide rule has
 * location_id = null; a per-location override sets location_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');             // 1..5
            $table->boolean('enabled')->default(false);
            $table->string('mode')->default('draft');          // auto | draft
            $table->text('tone')->nullable();                  // tone/template guidance
            $table->text('instruction')->nullable();           // extra instruction for the model
            $table->string('language')->nullable();            // null = auto-detect from review
            $table->timestamps();

            $table->unique(['location_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rules');
    }
};
