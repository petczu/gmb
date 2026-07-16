<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Models\PlaceReview;
use App\Models\Workspace;
use App\Services\Competitors\DataForSeoReviewsClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pull individual competitor reviews (with exact dates) into the central
 * place_reviews table via DataForSEO. Reviews are CENTRAL and keyed by
 * place_id, so a place shared by several workspaces is fetched once.
 *
 * Google exposes only a window of recent reviews and older ones fall out of
 * reach, so this captures them while they are still available. Full pull by
 * default; --delta tops up only the newest since the last run.
 *
 * The reviews add-on gating (which workspaces are entitled) is intentionally
 * NOT wired yet — pricing is undecided. For now this runs for the given
 * workspace's competitors (or all), behind the DATAFORSEO_REVIEWS_ENABLED
 * master switch.
 */
class BackfillCompetitorReviewsCommand extends Command
{
    protected $signature = 'competitors:backfill-reviews
        {workspace? : Workspace id or slug; omit for all}
        {--place=* : Specific place ids (skips the competitor lookup)}
        {--delta : Only fetch the newest reviews (top-up), not the full history}
        {--depth= : Override the max reviews pulled per place}';

    protected $description = 'Backfill individual competitor reviews from DataForSEO into place_reviews';

    public function handle(DataForSeoReviewsClient $client): int
    {
        if (! $client->configured()) {
            $this->warn('DataForSEO reviews are disabled (set DATAFORSEO_REVIEWS_ENABLED + credentials) — skipping.');

            return self::SUCCESS;
        }

        $placeIds = $this->resolvePlaceIds();
        if ($placeIds === []) {
            $this->info('No competitor places to backfill.');

            return self::SUCCESS;
        }

        $depth = $this->option('depth') !== null
            ? (int) $this->option('depth')
            : ($this->option('delta') ? 100 : null);

        $totalNew = 0;
        $failed = 0;

        foreach ($placeIds as $placeId) {
            try {
                $reviews = $client->fetch($placeId, $depth);
            } catch (Throwable $e) {
                $failed++;
                Log::warning('Competitor reviews backfill failed', ['place' => $placeId, 'error' => $e->getMessage()]);

                continue;
            }

            $new = $this->store($placeId, $reviews);
            $totalNew += $new;
            $this->line(sprintf('  %s: %d reviews (%d new)', $placeId, count($reviews), $new));
        }

        $this->info(sprintf(
            'Reviews backfill done: %d places, %d new reviews stored, %d failed.',
            count($placeIds),
            $totalNew,
            $failed,
        ));

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function resolvePlaceIds(): array
    {
        $explicit = array_values(array_filter((array) $this->option('place')));
        if ($explicit !== []) {
            return array_values(array_unique($explicit));
        }

        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()
                ->where('id', $this->argument('workspace'))
                ->orWhere('slug', $this->argument('workspace'))
                ->get();

        $placeIds = [];
        foreach ($workspaces as $workspace) {
            $this->inTenant($workspace, function () use (&$placeIds): void {
                $placeIds = array_merge($placeIds, Competitor::query()->pluck('place_id')->all());
            });
        }

        return array_values(array_unique(array_filter($placeIds)));
    }

    /**
     * Upsert reviews for a place; returns how many were newly inserted.
     *
     * @param  list<array{review_id: string, rating: ?float, reviewed_at: ?CarbonImmutable, author: ?string, text: ?string, language: ?string}>  $reviews
     */
    private function store(string $placeId, array $reviews): int
    {
        $new = 0;

        foreach ($reviews as $review) {
            $model = PlaceReview::updateOrCreate(
                ['place_id' => $placeId, 'review_id' => $review['review_id']],
                [
                    'rating' => $review['rating'],
                    'reviewed_at' => $review['reviewed_at'],
                    'author' => $review['author'],
                    'text' => $review['text'],
                    'language' => $review['language'],
                ],
            );

            if ($model->wasRecentlyCreated) {
                $new++;
            }
        }

        return $new;
    }

    private function inTenant(Workspace $workspace, callable $callback): void
    {
        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $callback();
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
