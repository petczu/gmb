<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user dashboard widget WIDTH overrides (full ↔ half), map of
 * order key => 'full'|1. Null/absent = the widget's own default span.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('dashboard_widget_spans')->nullable()->after('dashboard_widget_order');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('dashboard_widget_spans');
        });
    }
};
