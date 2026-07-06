<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Opt-out for the onboarding/product-tips email series (profile toggle).
            $table->boolean('product_emails')->default(true)->after('locale');
        });

        // One row per sent drip step — idempotency for the daily sender.
        Schema::create('drip_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email_key', 64);
            $table->timestamp('sent_at');

            $table->unique(['user_id', 'email_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_emails');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('product_emails');
        });
    }
};
