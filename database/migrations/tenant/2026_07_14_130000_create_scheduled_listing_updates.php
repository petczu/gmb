<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hours edits queued for a future date ("new schedule from Jan 1"),
        // applied by listings:apply-scheduled on the morning of apply_on.
        Schema::create('scheduled_listing_updates', function (Blueprint $table): void {
            $table->id();
            $table->json('location_ids');
            $table->json('opening_hours')->nullable();
            $table->json('special_hours')->nullable();
            $table->date('apply_on')->index();
            $table->timestamp('applied_at')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_listing_updates');
    }
};
