<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: reusable AI reply agents (persona + tone). Mirrors Localith's
 * "Customize agent": Name, Describe (system prompt), Tone of voice, and a
 * reply-in-the-review's-language toggle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description');                 // the system prompt / persona
            $table->string('tone')->default('professional'); // tone of voice
            $table->boolean('reply_native_language')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
