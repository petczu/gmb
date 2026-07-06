<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_schedules', function (Blueprint $table): void {
            // Per-schedule content block selection (report sections). Null =
            // use the workspace's last-used selection, like before.
            $table->json('blocks')->nullable()->after('compare');
        });
    }

    public function down(): void
    {
        Schema::table('report_schedules', function (Blueprint $table): void {
            $table->dropColumn('blocks');
        });
    }
};
