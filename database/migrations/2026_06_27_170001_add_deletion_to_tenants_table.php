<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: GDPR account deletion. When an owner requests deletion the workspace
 * is soft-locked (deletion_requested_at set) and access is blocked; a scheduled
 * job hard-purges it (drops the tenant DB) after the grace window. Until then
 * the owner can still cancel the request ("Cancel deletion").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('trial_ends_at');
            $table->unsignedBigInteger('deletion_requested_by')->nullable()->after('deletion_requested_at');

            $table->index('deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['deletion_requested_at']);
            $table->dropColumn(['deletion_requested_at', 'deletion_requested_by']);
        });
    }
};
