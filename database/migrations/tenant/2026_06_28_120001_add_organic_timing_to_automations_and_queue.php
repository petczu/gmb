<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT tables: "organic" auto-reply timing.
 *
 * Adds a per-automation random delay range + per-automation working hours,
 * and a `post_at` schedule timestamp on queued replies so a separate command
 * can post them when due instead of publishing instantly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            // Random delay window (minutes) applied before a reply is posted.
            $table->unsignedInteger('reply_delay_min_minutes')->default(0)->after('respect_working_hours');
            $table->unsignedInteger('reply_delay_max_minutes')->default(0)->after('reply_delay_min_minutes');

            // {"days":[1,2,3,4,5],"start":"09:00","end":"18:00"} — ISO weekdays 1=Mon..7=Sun.
            $table->json('working_hours')->nullable()->after('reply_delay_max_minutes');
        });

        Schema::table('auto_reply_queue', function (Blueprint $table) {
            // When a `scheduled` reply becomes due for posting.
            $table->timestamp('post_at')->nullable()->after('decided_at');

            $table->index(['status', 'post_at']);
        });
    }

    public function down(): void
    {
        Schema::table('auto_reply_queue', function (Blueprint $table) {
            $table->dropIndex(['status', 'post_at']);
            $table->dropColumn('post_at');
        });

        Schema::table('automations', function (Blueprint $table) {
            $table->dropColumn(['reply_delay_min_minutes', 'reply_delay_max_minutes', 'working_hours']);
        });
    }
};
