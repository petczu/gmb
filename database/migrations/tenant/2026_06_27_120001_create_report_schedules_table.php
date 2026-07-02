<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: scheduled delivery of the Monthly/Weekly Performance Report by
 * email. Reuses the same period/location/compare options as the on-screen
 * report; a daily command picks up the schedules that are due and queues the
 * PDF generation + send.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('frequency')->default('monthly'); // monthly | weekly
            $table->unsignedTinyInteger('send_day')->default(1); // monthly: day of month; weekly: ISO dow (1=Mon)
            $table->string('period')->default('last_month');  // DashboardPeriod preset
            $table->unsignedBigInteger('location_id')->nullable(); // null = all locations
            $table->boolean('compare')->default(true);
            $table->json('recipients')->nullable();           // explicit emails; falls back to workspace users
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['enabled', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
