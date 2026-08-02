<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers which workspace an MCP OAuth connection is scoped to. The MCP
 * endpoint is a single /mcp, so a user in several Pro workspaces picks one on
 * the consent screen; the choice is keyed by (user, OAuth client) and read
 * back by ResolveMcpWorkspace on every request. Central tables only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_workspace_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // oauth_clients.id and tenants.id are both UUID strings.
            $table->string('oauth_client_id');
            $table->string('workspace_id');
            $table->timestamps();

            $table->unique(['user_id', 'oauth_client_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_workspace_selections');
    }
};
