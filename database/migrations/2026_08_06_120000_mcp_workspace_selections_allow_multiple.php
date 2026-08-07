<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An MCP OAuth connection can now be bound to SEVERAL workspaces (checkboxes
 * on the consent screen), so the (user, client) pair is no longer unique —
 * one row per picked workspace. Central table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add the wider index BEFORE dropping the old one: MySQL refuses to
        // drop an index the user_id foreign key depends on unless another
        // index leading with user_id already exists.
        Schema::table('mcp_workspace_selections', function (Blueprint $table) {
            $table->unique(['user_id', 'oauth_client_id', 'workspace_id'], 'mcp_selection_unique');
        });
        Schema::table('mcp_workspace_selections', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'oauth_client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('mcp_workspace_selections', function (Blueprint $table) {
            $table->unique(['user_id', 'oauth_client_id']);
        });
        Schema::table('mcp_workspace_selections', function (Blueprint $table) {
            $table->dropUnique('mcp_selection_unique');
        });
    }
};
