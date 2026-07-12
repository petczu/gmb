<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The inviter picks the invitee's language: the invitation email goes out in
 * it, and the account adopts it on accept (notifications, reports).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->string('locale', 5)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
