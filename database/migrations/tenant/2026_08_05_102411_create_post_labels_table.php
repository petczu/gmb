<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colored labels for organizing calendar posts (e.g. "For review", "Approved").
 * Tenant-scoped. Assignment is a JSON array of label ids on the post, matching
 * how location_ids/source_ids are already stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('color', 20)->default('blue');
            $table->timestamps();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->json('label_ids')->nullable()->after('location_ids');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('label_ids');
        });

        Schema::dropIfExists('post_labels');
    }
};
