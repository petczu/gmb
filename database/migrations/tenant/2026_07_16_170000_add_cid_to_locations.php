<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Maps CID (customer id) per location. Several GBP locations can sit
 * under one Zernio account, and the account's external-post feed carries posts
 * for all of them; the per-post CID (from its platformPostUrl) is the only
 * reliable way to attribute an imported post to the right location.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('cid', 40)->nullable()->after('place_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('cid');
        });
    }
};
