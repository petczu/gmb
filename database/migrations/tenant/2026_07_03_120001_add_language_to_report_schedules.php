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
            // Language of the generated PDF + email. A report is ONE document
            // for all recipients, so language is a property of the schedule,
            // not of each recipient.
            $table->string('language', 5)->default('en')->after('period');
        });
    }

    public function down(): void
    {
        Schema::table('report_schedules', function (Blueprint $table): void {
            $table->dropColumn('language');
        });
    }
};
