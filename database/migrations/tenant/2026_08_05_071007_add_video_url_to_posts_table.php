<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Business posts now accept a video as well as an image. A post carries
 * at most one media item, so a separate nullable column keeps the image path
 * untouched; the publisher prefers the video when both are somehow present.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('video_url')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('video_url');
        });
    }
};
