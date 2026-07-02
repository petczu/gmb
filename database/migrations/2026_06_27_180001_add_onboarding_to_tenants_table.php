<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: first-run onboarding. A brand-new workspace (created at registration)
 * has onboarding_completed_at = null and is guided through company details,
 * plan selection and connecting its first location. EXISTING workspaces are
 * backfilled as already onboarded so they never see the guide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('trial_ends_at');
        });

        // Existing workspaces are already up and running — mark them complete.
        DB::table('tenants')->whereNull('onboarding_completed_at')->update([
            'onboarding_completed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
