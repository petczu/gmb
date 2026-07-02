<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT table: AI-generated replies awaiting decision/publish. `draft`-mode
 * rules land here as `pending`; `auto`-mode rules publish immediately and are
 * recorded here as `published` for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->longText('generated_text');
            $table->string('status')->default('pending');   // pending | approved | published | skipped | failed
            $table->string('mode')->default('draft');        // auto | draft (rule mode at generation time)
            $table->string('model')->nullable();
            $table->unsignedInteger('credits_spent')->default(0);
            $table->text('error')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable(); // central user id, no FK
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_queue');
    }
};
