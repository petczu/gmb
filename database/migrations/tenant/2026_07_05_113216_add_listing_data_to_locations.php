<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            // Last pushed listing profile (description, hours, special hours):
            // the Zernio GET listing response does not echo these back, so the
            // edit form is prefilled from this local copy.
            $table->json('listing_data')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('listing_data');
        });
    }
};
