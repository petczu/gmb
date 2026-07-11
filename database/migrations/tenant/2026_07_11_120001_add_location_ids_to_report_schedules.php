<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report schedules can now cover several locations (e.g. two rooms in one
 * city). `location_ids` (json list) supersedes the single `location_id`,
 * which is kept and backfilled from so existing schedules keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_schedules', function (Blueprint $table): void {
            $table->json('location_ids')->nullable()->after('location_id');
        });

        DB::table('report_schedules')
            ->whereNotNull('location_id')
            ->eachById(function (object $row): void {
                DB::table('report_schedules')
                    ->where('id', $row->id)
                    ->update(['location_ids' => json_encode([(int) $row->location_id])]);
            });
    }

    public function down(): void
    {
        Schema::table('report_schedules', function (Blueprint $table): void {
            $table->dropColumn('location_ids');
        });
    }
};
