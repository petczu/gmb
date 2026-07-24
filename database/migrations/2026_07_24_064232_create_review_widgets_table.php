<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Review-showcase widgets live in the CENTRAL DB. The embed is loaded on the
     * customer's OWN website (cross-origin, no session, no tenancy), so the row
     * and a pre-built snapshot of the selected reviews must be servable without
     * booting the tenant DB on every hit (same reasoning as review_pages).
     */
    public function up(): void
    {
        Schema::create('review_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('workspace_id')->index();
            // Public opaque id used in the embed URL (/w/{token}.js).
            $table->string('token', 40)->unique();
            $table->string('name')->nullable();
            // Layout, card, header, colours and the review-selection filters.
            $table->json('settings');
            // Pre-rendered, tenancy-free copy of the selected reviews + header
            // aggregate. Rebuilt on save, on review sync and on a schedule.
            $table->json('snapshot')->nullable();
            $table->timestamp('refreshed_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_widgets');
    }
};
