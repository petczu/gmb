<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: record the real cost of each AI call on the ledger — the model used,
 * input/output tokens, and the computed USD cost. This turns the credit ledger
 * into a complete usage log (basis for the future AI overage / top-up feature).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_credit_ledger', function (Blueprint $table) {
            $table->string('model')->nullable()->after('reason');
            $table->unsignedInteger('input_tokens')->nullable()->after('model');
            $table->unsignedInteger('output_tokens')->nullable()->after('input_tokens');
            $table->decimal('cost_usd', 12, 6)->nullable()->after('output_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('ai_credit_ledger', function (Blueprint $table) {
            $table->dropColumn(['model', 'input_tokens', 'output_tokens', 'cost_usd']);
        });
    }
};
