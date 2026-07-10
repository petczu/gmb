<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember which AI agent generated an auto-reply, so the Approvals "Review &
 * reply" slide-over can preselect that agent instead of falling back to the
 * default one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_reply_queue', function (Blueprint $table): void {
            $table->unsignedBigInteger('ai_agent_id')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('auto_reply_queue', function (Blueprint $table): void {
            $table->dropColumn('ai_agent_id');
        });
    }
};
