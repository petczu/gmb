<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private sticky notes on the posts calendar (Planable-style).
        Schema::create('post_notes', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->index();
            $table->text('body')->nullable();
            $table->string('color', 20)->default('yellow');
            $table->string('tag', 60)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });

        // External ICS feeds overlaid on the calendar (holidays, bookings,
        // other SMM calendars). Events are replaced wholesale on each sync.
        Schema::create('external_calendars', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url', 2048);
            $table->string('color', 20)->default('green');
            $table->boolean('enabled')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });

        Schema::create('external_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_calendar_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('title', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_events');
        Schema::dropIfExists('external_calendars');
        Schema::dropIfExists('post_notes');
    }
};
