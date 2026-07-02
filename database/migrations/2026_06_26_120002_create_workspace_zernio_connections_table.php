<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central table: the Zernio account connection for a workspace.
 * Stored centrally so scheduled sync jobs can iterate workspaces without a
 * tenant context, then initialize the right tenant to write reviews.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_zernio_connections', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id');
            $table->string('zernio_account_id');
            $table->text('access_token')->nullable();   // encrypted at the model layer
            $table->string('status')->default('connected'); // connected | revoked | error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_zernio_connections');
    }
};
