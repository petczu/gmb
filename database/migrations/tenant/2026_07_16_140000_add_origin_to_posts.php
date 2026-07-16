<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Imported Google posts: previously-published local posts synced back from the
 * platform (via Zernio external posts). `origin` distinguishes them from posts
 * authored in the app, `platform_post_id` dedupes on the Google-native id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('origin', 20)->default('app')->after('status');
            $table->string('platform_post_id')->nullable()->after('external_ids')->index();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['origin', 'platform_post_id']);
        });
    }
};
