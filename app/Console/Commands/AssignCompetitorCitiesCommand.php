<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\Competitors\CompetitorGeo;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backfill: store coordinates for own locations and competitors (from the
 * Places API), then re-scope each competitor to the own locations in its city
 * by geographic distance (via CompetitorGeo). Only touches single-competitor
 * battles that are still tied to ALL locations (the add-time default) so named
 * groups are left alone; pass --all to also re-scope narrowed battles (e.g.
 * after adding a location so its city picks up the new sibling).
 */
class AssignCompetitorCitiesCommand extends Command
{
    protected $signature = 'competitors:assign-cities
        {workspace? : Limit to a single workspace id or slug}
        {--dry-run : Show what would change without saving}
        {--all : Also re-scope competitors already narrowed to specific locations (use after adding a location)}';

    protected $description = 'Store coordinates and assign each competitor to the own locations in its city (by distance)';

    public function handle(PlacesClient $places): int
    {
        if (! $places->configured()) {
            $this->warn('Places API is not configured; nothing to do.');

            return self::SUCCESS;
        }

        $workspaces = $this->argument('workspace') !== null
            ? Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get()
            : Workspace::query()->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $this->processWorkspace($workspace, $places);
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function processWorkspace(Workspace $workspace, PlacesClient $places): void
    {
        $dry = (bool) $this->option('dry-run');
        $rescopeAll = (bool) $this->option('all');

        // 1. Backfill coordinates onto own locations and competitors.
        $this->backfillCoordinates($places, $dry);

        $locations = Location::query()->get();
        if ($locations->count() < 2) {
            $this->line("{$workspace->slug}: fewer than 2 locations, skipping.");

            return;
        }

        $allIds = $locations->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();

        // 2. Re-scope each single-competitor battle by distance.
        $competitors = Competitor::query()->with('battle')->get();
        $changed = 0;

        foreach ($competitors as $competitor) {
            $battle = $competitor->battle;
            if ($battle === null || $battle->competitors()->count() !== 1) {
                continue; // grouped battles are intentional — leave them.
            }

            $current = $battle->ownLocationIds();
            sort($current);
            if (! $rescopeAll && $current !== $allIds) {
                continue;
            }

            $cityIds = CompetitorGeo::ownLocationIdsFor(
                $competitor->latitude,
                $competitor->longitude,
                $locations,
            );

            if ($cityIds === [] || $cityIds === $current) {
                continue;
            }

            $names = $locations->whereIn('id', $cityIds)->pluck('name')->implode(', ');
            $this->line(sprintf('  · %s → %s%s', $competitor->name, $names, $dry ? ' [dry-run]' : ''));

            if (! $dry) {
                $battle->update(['own_location_ids' => $cityIds]);
                $competitor->update(['location_id' => $cityIds[0]]);
            }
            $changed++;
        }

        $this->line("{$workspace->slug}: {$changed} competitor(s) ".($dry ? 'would be ' : '').'assigned.');
    }

    /** Fetch + store lat/lng for any located row that is still missing them. */
    private function backfillCoordinates(PlacesClient $places, bool $dry): void
    {
        $rows = Location::query()->whereNotNull('place_id')->whereNull('latitude')->get()
            ->concat(Competitor::query()->whereNotNull('place_id')->whereNull('latitude')->get());

        foreach ($rows as $row) {
            $coords = $this->coordinates($places, (string) $row->place_id);
            if ($coords === null) {
                continue;
            }
            if (! $dry) {
                $row->update(['latitude' => $coords['lat'], 'longitude' => $coords['lng']]);
            }
        }
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function coordinates(PlacesClient $places, string $placeId): ?array
    {
        if ($placeId === '') {
            return null;
        }

        try {
            return $places->coordinates($placeId);
        } catch (Throwable) {
            return null;
        }
    }
}
