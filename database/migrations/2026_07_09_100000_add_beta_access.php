<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Private beta gate (CENTRAL): users.approved_at marks who may use the app;
 * beta_allowlist holds emails that skip the application queue entirely.
 * Existing users are backfilled as approved so nobody gets locked out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('email_verified_at');
        });

        DB::table('users')->update(['approved_at' => now()]);

        Schema::create('beta_allowlist', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beta_allowlist');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('approved_at');
        });
    }
};
