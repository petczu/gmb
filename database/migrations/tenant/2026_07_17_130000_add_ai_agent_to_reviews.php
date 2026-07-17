<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which AI agent produced a review's published reply (null for human/manual,
 * MCP, API or Google-side replies). Surfaces "replied by" on the Reviews table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->unsignedBigInteger('ai_agent_id')->nullable()->after('reply_source');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn('ai_agent_id');
        });
    }
};
