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
            // Zernio REST listing id (a different id space than external_id,
            // which comes from the connect API). Filled lazily by SourceIdResolver.
            $table->string('source_id')->nullable()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn('source_id');
        });
    }
};
