<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20); // update | offer | event | photo
            $table->text('caption')->nullable();
            $table->string('title')->nullable(); // offer / event
            $table->string('cta_type', 20)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('photo_category', 30)->nullable(); // photo type only
            $table->dateTime('starts_at')->nullable(); // offer / event
            $table->dateTime('ends_at')->nullable();
            $table->string('voucher_code')->nullable(); // offer only
            $table->string('redeem_url', 2048)->nullable();
            $table->string('terms_url', 2048)->nullable();
            $table->json('location_ids'); // tenant location ids
            $table->json('source_ids'); // Zernio listing ids the post went to
            $table->dateTime('scheduled_at')->nullable(); // UTC
            // published | in_progress | failed | scheduled
            $table->string('status', 20)->index();
            $table->json('external_ids')->nullable(); // ids returned by Zernio
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
