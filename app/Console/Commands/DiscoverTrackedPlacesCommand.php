<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TrackedPlace;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Bulk-fill the central tracked_places watchlist from Google Places text
 * search: one search per --area ("<query> in <area>", up to 60 results each),
 * deduped by place_id. Each new place gets its first snapshot right away so
 * competitor history starts today. CENTRAL tables only — no tenant touched.
 *
 * Example:
 *   php artisan tracked-places:discover "escape room" --area="Vienna, Austria" --area="Graz, Austria"
 */
class DiscoverTrackedPlacesCommand extends Command
{
    protected $signature = 'tracked-places:discover
        {query : What to search for (e.g. "escape room")}
        {--area=* : City/region to search in, repeatable}
        {--pages=3 : Max result pages per area (20 places each, Google caps at 3)}
        {--dry-run : List what would be added without writing}';

    protected $description = 'Fill the tracked-places watchlist from Google Places search, one search per area';

    public function handle(PlacesClient $places): int
    {
        if (! $places->configured()) {
            $this->error('Google Places API key is not configured (services.google.places_key).');

            return self::FAILURE;
        }

        $areas = (array) $this->option('area');
        if ($areas === []) {
            $this->error('Pass at least one --area="City, Country".');

            return self::FAILURE;
        }

        $query = (string) $this->argument('query');
        $pages = max(1, (int) $this->option('pages'));
        $known = TrackedPlace::query()->pluck('place_id')->flip();
        $seenThisRun = [];
        $added = 0;

        foreach ($areas as $area) {
            try {
                $found = $places->searchAll("{$query} in {$area}", $pages);
            } catch (Throwable $e) {
                $this->warn("{$area}: search failed ({$e->getMessage()})");

                continue;
            }

            $new = 0;
            foreach ($found as $place) {
                $id = $place['place_id'];

                if ($id === '' || isset($known[$id]) || isset($seenThisRun[$id])) {
                    continue;
                }

                $seenThisRun[$id] = true;
                $new++;

                if ($this->option('dry-run')) {
                    $this->line("  would add: {$place['name']} — ".($place['address'] ?? ''));

                    continue;
                }

                TrackedPlace::create([
                    'place_id' => $id,
                    'name' => $place['name'],
                    'address' => $place['address'],
                ]);

                CompetitorTrends::recordPlace($id, $place['rating'], $place['reviews_count']);
                $added++;
            }

            $this->line(sprintf('%s: %d found, %d new', $area, count($found), $new));
        }

        $this->info($this->option('dry-run')
            ? 'Dry run: '.count($seenThisRun).' place(s) would be added.'
            : "Added {$added} place(s) to the watchlist.");

        return self::SUCCESS;
    }
}
