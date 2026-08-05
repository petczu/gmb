<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: public share links for a Google post, mirroring report_shares. The
 * rendered card HTML is snapshotted here so the link works with no login and
 * no tenant context. ONE share per post (unique workspace + post), so
 * re-sharing updates the same row and keeps the same link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('workspace_id');
            $table->unsignedBigInteger('post_id');
            $table->string('title')->nullable();
            $table->longText('html');
            $table->string('password')->nullable();  // hashed, optional
            $table->date('access_from')->nullable(); // optional access window
            $table->date('access_until')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'post_id']);
            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_shares');
    }
};
