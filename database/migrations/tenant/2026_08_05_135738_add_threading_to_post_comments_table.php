<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comment collaboration: replies (parent_id), emoji reactions (JSON list of
 * {emoji, user_id, user_name} entries) and an edited marker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('post_id')->index();
            $table->json('reactions')->nullable()->after('mentioned_user_ids');
            $table->timestamp('edited_at')->nullable()->after('reactions');
        });
    }

    public function down(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'reactions', 'edited_at']);
        });
    }
};
