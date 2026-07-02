<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: public share links for a generated report. The rendered HTML is
 * copied here so the public link works with no login and no tenant context.
 * Exactly ONE share per report (unique workspace + report), so re-sharing
 * updates the same row and keeps the same link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('workspace_id');
            $table->unsignedBigInteger('generated_report_id');
            $table->string('title')->nullable();
            $table->longText('html');
            $table->string('password')->nullable();      // hashed, optional
            $table->date('access_from')->nullable();      // optional access window
            $table->date('access_until')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'generated_report_id']);
            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_shares');
    }
};
