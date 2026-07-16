<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BackfillPlaceReviewsJob;
use App\Models\Competitor;
use App\Models\Workspace;
use App\Services\Competitors\DataForSeoReviewsClient;
use Illuminate\Console\Command;

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

        // The reviews endpoint is async (standard queue up to ~45 min). Hand
        // each place to a queued job so this returns immediately and no worker
        // blocks — the job posts the task, then polls itself to completion.
        foreach ($placeIds as $placeId) {
            BackfillPlaceReviewsJob::dispatch($placeId, $depth);
        }

        $this->info(sprintf('Dispatched %d review backfill job(s). Results arrive as DataForSEO completes each task.', count($placeIds)));

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
