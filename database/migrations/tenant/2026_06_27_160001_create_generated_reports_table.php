<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: a saved snapshot of each AI-generated report so they can be
 * re-viewed/downloaded later without re-spending an AI generation. We store the
 * already-rendered HTML (point-in-time), not the raw data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('period_label');
            $table->string('language', 5)->default('en');
            $table->longText('html');
            $table->unsignedBigInteger('generated_by')->nullable(); // central user id
            $table->string('generated_by_name')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
