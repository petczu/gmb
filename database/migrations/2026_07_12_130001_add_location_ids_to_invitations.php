<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The inviter can scope a member to specific locations: the selection rides on
 * the invitation and is written to the workspace_user pivot on accept, driving
 * both location access and location-scoped notification routing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->json('location_ids')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropColumn('location_ids');
        });
    }
};
