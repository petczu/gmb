<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Workspace;
use App\Services\Competitors\LocationPlaceResolver;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;

/**
 * Backfills the Google Maps place_id for already-connected locations that lack
 * one (one Places Text Search each). Once set, competitors:refresh reuses the
 * location's synced rating/reviews instead of a paid Places call for that place.
 *
 *   php artisan locations:resolve-place-ids            # all workspaces
 *   php artisan locations:resolve-place-ids acme       # one workspace
 *   php artisan locations:resolve-place-ids --dry-run  # list, don't write
 */
class ResolveLocationPlaceIdsCommand extends Command
{
    protected $signature = 'locations:resolve-place-ids
        {workspace? : Workspace id or slug; omit for all}
        {--dry-run : Show what would be resolved without calling Places or writing}';

    protected $description = 'Resolve + store the Google place_id for connected locations that lack one';

    public function handle(PlacesClient $places, LocationPlaceResolver $resolver): int
    {
        if (! $places->configured()) {
            $this->error('GOOGLE_PLACES_API_KEY is not set.');

            return self::FAILURE;
        }

        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        $resolved = 0;
        $missed = 0;

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $pending = Location::query()
                    ->where(fn ($q) => $q->whereNull('place_id')->orWhere('place_id', ''))
                    ->get();

                foreach ($pending as $location) {
                    if ($this->option('dry-run')) {
                        $this->line(sprintf('  %s — %s: %s', $workspace->slug, $location->name, $location->address ?? '—'));

                        continue;
                    }

                    $placeId = $resolver->resolve($location);

                    if ($placeId !== null) {
                        $resolved++;
                        $this->line(sprintf('  %s — %s → %s', $workspace->slug, $location->name, $placeId));
                    } else {
                        $missed++;
                        $this->warn(sprintf('  %s — %s: no match', $workspace->slug, $location->name));
                    }
                }
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        $this->info($this->option('dry-run')
            ? 'Dry run complete.'
            : sprintf('Resolved %d place id(s), %d unmatched.', $resolved, $missed));

        return self::SUCCESS;
    }
}
