<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL: pending invitations for people to join a workspace. The token-based
 * accept link works pre-tenant (the invitee may not have an account yet), so
 * this lives on the central connection alongside users + tenants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('workspace_id'); // references tenants.id (string UUID)
            $table->string('email')->index();
            $table->string('role')->default('member'); // owner | admin | member
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['workspace_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
