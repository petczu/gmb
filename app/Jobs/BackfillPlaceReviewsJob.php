<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PlaceReview;
use App\Services\Competitors\DataForSeoReviewsClient;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Backfill one place's reviews from DataForSEO without blocking a worker.
 *
 * The reviews endpoint is asynchronous (standard queue up to ~45 min), so
 * this runs in two phases, each a fresh dispatch (mutations don't survive
 * release()):
 *   1. taskId null → create the task, re-dispatch (delayed) carrying its id.
 *   2. taskId set  → poll; not ready → re-dispatch (delayed); ready → store.
 *
 * Reviews are CENTRAL (place_reviews, keyed by place_id) — no tenant context.
 */
class BackfillPlaceReviewsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Poll re-dispatches before giving up (~1/min → ~1 h, covers the 45-min queue). */
    private const MAX_POLLS = 60;

    private const POLL_DELAY_SECONDS = 60;

    /** Retries for hard failures (HTTP errors on post/get), not the poll loop. */
    public int $tries = 3;

    public function __construct(
        public string $placeId,
        public ?int $depth = null,
        public ?string $taskId = null,
        public int $poll = 0,
    ) {}

    public function handle(DataForSeoReviewsClient $client): void
    {
        if (! $client->configured()) {
            return;
        }

        // Phase 1 — create the task, hand its id to a delayed follow-up.
        if ($this->taskId === null) {
            $taskId = $client->postTask($this->placeId, $this->depth);
            self::dispatch($this->placeId, $this->depth, $taskId)
                ->delay(now()->addSeconds(self::POLL_DELAY_SECONDS));

            return;
        }

        // Phase 2 — poll until DataForSEO has the results ready.
        $reviews = $client->getTask($this->taskId);

        if ($reviews === null) {
            if ($this->poll + 1 < self::MAX_POLLS) {
                self::dispatch($this->placeId, $this->depth, $this->taskId, $this->poll + 1)
                    ->delay(now()->addSeconds(self::POLL_DELAY_SECONDS));
            }

            return;
        }

        $this->store($reviews);
    }

    /**
     * @param  list<array{review_id: string, rating: ?float, reviewed_at: ?CarbonImmutable, author: ?string, text: ?string, language: ?string}>  $reviews
     */
    private function store(array $reviews): void
    {
        foreach ($reviews as $review) {
            PlaceReview::updateOrCreate(
                ['place_id' => $this->placeId, 'review_id' => $review['review_id']],
                [
                    'rating' => $review['rating'],
                    'reviewed_at' => $review['reviewed_at'],
                    'author' => $review['author'],
                    'text' => $review['text'],
                    'language' => $review['language'],
                ],
            );
        }
    }
}
