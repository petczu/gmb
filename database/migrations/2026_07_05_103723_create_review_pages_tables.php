<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Review-collection pages live in the CENTRAL DB: they are served publicly
     * by slug or custom domain BEFORE any tenancy/session exists (same reason
     * as report shares and api_keys).
     */
    public function up(): void
    {
        Schema::create('review_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('workspace_id')->index();
            $table->string('slug', 64)->unique();
            $table->string('custom_domain')->nullable()->unique();
            // Look & content: headline/subtitle per locale, theme, accent,
            // logo source, target buttons (google/tripadvisor/custom).
            $table->json('settings');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Daily aggregated counters — enough for views/clicks/CTR analytics
        // without storing per-visitor data (GDPR-friendly, no cookies).
        Schema::create('review_page_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_page_id')->constrained('review_pages')->cascadeOnDelete();
            $table->date('day');
            // 'view' for page loads, otherwise the clicked target key.
            $table->string('metric', 32);
            $table->unsignedInteger('count')->default(0);

            $table->unique(['review_page_id', 'day', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_page_stats');
        Schema::dropIfExists('review_pages');
    }
};
