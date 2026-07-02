<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: review-reply automations (Localith "Flow + Content").
 * Flow = trigger + location scope + rating filter + options.
 * Content = a fixed default message OR an AI agent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('trigger')->default('new_review'); // new review on Google

            // Flow
            $table->json('rating_filter')->nullable();        // [5,4,..] or null = any
            $table->boolean('all_locations')->default(true);
            $table->json('location_ids')->nullable();         // when all_locations = false
            $table->boolean('respect_working_hours')->default(false);
            $table->boolean('reply_to_previous')->default(false);
            $table->boolean('approve_before_posting')->default(false); // default = auto-publish

            // Content
            $table->string('content_type')->default('ai_agent'); // ai_agent | default_message
            $table->text('default_message')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();

            $table->timestamps();

            $table->index(['enabled', 'trigger']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
