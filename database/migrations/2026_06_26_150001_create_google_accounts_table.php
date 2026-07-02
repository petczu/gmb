<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central table: Google Business accounts a workspace has connected (via the
 * single platform-wide Zernio key). A workspace can connect/remove several.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id');
            $table->string('zernio_account_id');
            $table->string('name')->nullable();
            $table->string('status')->default('connected'); // connected | revoked | error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['workspace_id', 'zernio_account_id']);
        });

        // Superseded by google_accounts (per-workspace token is gone — one ENV key now).
        Schema::dropIfExists('workspace_zernio_connections');
    }

    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
