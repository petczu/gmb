<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user dashboard widget ORDER (drag-and-drop on the grid). Kept separate
 * from dashboard_widgets (the show/hide selection): null = default order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('dashboard_widget_order')->nullable()->after('dashboard_widgets');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('dashboard_widget_order');
        });
    }
};
