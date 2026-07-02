<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central, append-only AI credit ledger. Balance = SUM(delta). Each AI
 * generation debits credits; top-ups credit them. balance_after is stored for
 * audit/readability.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id');
            $table->integer('delta');                 // +credit, -debit
            $table->integer('balance_after');
            $table->string('reason');                 // auto_reply | manual_ai_draft | topup | adjustment
            $table->string('ref_type')->nullable();   // e.g. review / queue item
            $table->string('ref_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_ledger');
    }
};
