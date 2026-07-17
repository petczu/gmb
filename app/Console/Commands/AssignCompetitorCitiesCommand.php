<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backfill: scope each existing competitor to the nearest of your own locations
 * (its city), by geographic distance via the Places API. Only touches
 * single-competitor battles that are still tied to ALL locations (the add-time
 * default) so manual "Edit locations" choices and named groups are left alone.
 */
class AssignCompetitorCitiesCommand extends Command
{
    protected $signature = 'competitors:assign-cities
        {workspace? : Limit to a single workspace id or slug}
        {--dry-run : Show what would change without saving}';

    protected $description = 'Assign each competitor to its nearest own location (city) by distance';

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
        $locations = Location::query()->whereNotNull('place_id')->get();
        if ($locations->count() < 2) {
            $this->line("{$workspace->slug}: fewer than 2 located locations, skipping.");

            return;
        }

        $allIds = Location::query()->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();

        // Resolve each own location's coordinates once.
        $points = [];
        foreach ($locations as $location) {
            $coords = $this->coordinates($places, (string) $location->place_id);
            if ($coords !== null) {
                $points[(int) $location->id] = ['name' => (string) $location->name, 'coords' => $coords];
            }
        }

        if ($points === []) {
            $this->line("{$workspace->slug}: no location coordinates available, skipping.");

            return;
        }

        $competitors = Competitor::query()->with('battle')->whereNotNull('place_id')->get();
        $dry = (bool) $this->option('dry-run');
        $changed = 0;

        foreach ($competitors as $competitor) {
            $battle = $competitor->battle;
            if ($battle === null || $battle->competitors()->count() !== 1) {
                continue; // grouped battles are intentional — leave them.
            }

            // Only touch still-unscoped battles (default = all locations).
            $current = $battle->ownLocationIds();
            sort($current);
            if ($current !== $allIds) {
                continue;
            }

            $coords = $this->coordinates($places, (string) $competitor->place_id);
            if ($coords === null) {
                $this->line("  · {$competitor->name}: no coordinates, skipped");

                continue;
            }

            $nearestId = null;
            $nearestKm = INF;
            foreach ($points as $locationId => $point) {
                $km = $this->distanceKm($coords, $point['coords']);
                if ($km < $nearestKm) {
                    $nearestKm = $km;
                    $nearestId = $locationId;
                }
            }

            if ($nearestId === null) {
                continue;
            }

            $this->line(sprintf('  · %s → %s (%.1f km)%s', $competitor->name, $points[$nearestId]['name'], $nearestKm, $dry ? ' [dry-run]' : ''));

            if (! $dry) {
                $battle->update(['own_location_ids' => [$nearestId]]);
                $competitor->update(['location_id' => $nearestId]);
            }
            $changed++;
        }

        $this->line("{$workspace->slug}: {$changed} competitor(s) ".($dry ? 'would be ' : '').'assigned.');
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

    /**
     * Great-circle distance in km between two lat/lng points (haversine).
     *
     * @param  array{lat: float, lng: float}  $a
     * @param  array{lat: float, lng: float}  $b
     */
    private function distanceKm(array $a, array $b): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);

        $h = sin($dLat / 2) ** 2
            + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;

        return $earth * 2 * asin(min(1.0, sqrt($h)));
    }
}
