<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * API keys live in the CENTRAL DB: they authenticate a workspace before
     * tenancy is initialized, so the lookup must happen on the central
     * connection (like Cashier subscriptions).
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->id();
            // tenants.id is a uuid (varchar) — the owning workspace.
            $table->string('workspace_id')->index();
            $table->string('name');
            // First chars of the raw key, shown in the UI to identify it.
            $table->string('prefix', 16);
            // SHA-256 of the raw key — never the raw key itself.
            $table->string('key_hash', 64)->unique();
            // Granted scopes (ApiAbilities).
            $table->json('abilities');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
