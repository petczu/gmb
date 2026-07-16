<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CENTRAL individual competitor reviews pulled from DataForSEO, keyed by
     * the public Google place_id and shared across workspaces (like
     * place_snapshots). Google exposes only a window of recent reviews and
     * older ones scroll out of reach, so we capture them once here; exact
     * per-day charts and (later, once legally cleared) sentiment reports read
     * from these rows.
     *
     * PII note: author names / review text are third-party personal data.
     * They are stored but NOT surfaced in the app until the competitor-insights
     * add-on is legally signed off. See the Competitors add-on discussion.
     */
    public function up(): void
    {
        Schema::create('place_reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('place_id', 160);
            // DataForSEO's stable per-review id — dedupes across backfills.
            $table->string('review_id', 191);
            $table->decimal('rating', 2, 1)->nullable();
            $table->dateTime('reviewed_at')->nullable()->index();
            $table->string('author')->nullable();
            $table->text('text')->nullable();
            $table->string('language', 8)->nullable();
            $table->timestamps();

            $table->unique(['place_id', 'review_id']);
            $table->index(['place_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_reviews');
    }
};
