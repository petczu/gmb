<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Snapshots moved to the CENTRAL place_snapshots table (keyed by place_id). */
    public function up(): void
    {
        Schema::dropIfExists('competitor_snapshots');
    }

    public function down(): void
    {
        // Intentionally empty — recreate via the original tenant migration if ever needed.
    }
};
