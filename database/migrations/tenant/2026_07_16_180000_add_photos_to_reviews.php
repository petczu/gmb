<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviewer-uploaded photos on Google Business reviews. photo_count is the number
 * of attached photos (videos not counted); photos holds their image urls. Other
 * platforms always have 0 / empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->unsignedSmallInteger('photo_count')->default(0)->after('text');
            $table->json('photos')->nullable()->after('photo_count');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn(['photo_count', 'photos']);
        });
    }
};
