<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide knowledge base of holiday explainers. CENTRAL table: an AI
 * brief written once (per day + title + country + language) is served to
 * every tenant without another AI call.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_briefs', function (Blueprint $table) {
            $table->id();
            // sha1 of normalized (country, date, title, locale).
            $table->string('key_hash', 40)->unique();
            $table->string('country', 120);
            $table->date('date');
            $table->string('title', 160);
            $table->string('locale', 8);
            $table->text('brief');
            $table->timestamps();

            $table->index(['country', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_briefs');
    }
};
