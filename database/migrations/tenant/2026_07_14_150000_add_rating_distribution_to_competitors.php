<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitors', function (Blueprint $table): void {
            // Star breakdown {1..5 => count} from the nightly refresh. Only
            // DataForSEO returns it; null when the snapshot came from the
            // Google Places API (which has no distribution).
            $table->json('rating_distribution')->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table): void {
            $table->dropColumn('rating_distribution');
        });
    }
};
