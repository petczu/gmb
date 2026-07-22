<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Location;
use Illuminate\Support\Collection;

/**
 * Geographic scoping for competitors: a competitor is compared against the own
 * locations in its city, decided by distance so districts/spelling never matter.
 * No manual "which locations" choice — the own_location_ids of a competitor's
 * battle are derived from its coordinates.
 */
class CompetitorGeo
{
    /** Own locations within this radius of a competitor count as its city. */
    public const RADIUS_KM = 35.0;

    /**
     * Does any of the given competitors sit within RADIUS_KM of any of the
     * selected locations, by the competitor's OWN coordinates? Returns null when
     * coordinates are unavailable on either side — the caller should then fall
     * back to the battle's own_location_ids. Used to scope the dashboard's
     * competitor widgets to a selected location without letting a grouped
     * battle's multi-city own_location_ids leak competitors from other cities.
     *
     * @param  Collection<int, object{latitude: ?float, longitude: ?float}>  $competitors
     * @param  Collection<int, object{latitude: ?float, longitude: ?float}>  $selectedLocations
     */
    public static function anyCompetitorInSelected(Collection $competitors, Collection $selectedLocations): ?bool
    {
        $located = $competitors->filter(fn (object $c): bool => $c->latitude !== null && $c->longitude !== null);
        $targets = $selectedLocations->filter(fn (object $l): bool => $l->latitude !== null && $l->longitude !== null);

        if ($located->isEmpty() || $targets->isEmpty()) {
            return null;
        }

        return $located->contains(fn (object $c): bool => $targets->contains(
            fn (object $l): bool => self::distanceKm((float) $l->latitude, (float) $l->longitude, (float) $c->latitude, (float) $c->longitude) <= self::RADIUS_KM,
        ));
    }

    /** Great-circle distance in km between two points (haversine). */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * asin(min(1.0, sqrt($h)));
    }

    /**
     * The own-location ids that share a city with the given point: every own
     * location within RADIUS_KM, or the single nearest one if none fall inside.
     * Returns all located locations' ids when the point has no coordinates (so a
     * competitor we couldn't geolocate still shows everywhere rather than
     * vanish). Locations without coordinates are ignored.
     *
     * @param  Collection<int, Location>  $locations
     * @return list<int>
     */
    public static function ownLocationIdsFor(?float $lat, ?float $lng, Collection $locations): array
    {
        $located = $locations->filter(fn (Location $l): bool => $l->latitude !== null && $l->longitude !== null);

        if ($located->isEmpty()) {
            return $locations->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        if ($lat === null || $lng === null) {
            return $located->pluck('id')->map(fn ($id): int => (int) $id)->all();
        }

        $nearestId = null;
        $nearestKm = INF;
        $cityIds = [];

        foreach ($located as $location) {
            $km = self::distanceKm($lat, $lng, (float) $location->latitude, (float) $location->longitude);
            if ($km < $nearestKm) {
                $nearestKm = $km;
                $nearestId = (int) $location->id;
            }
            if ($km <= self::RADIUS_KM) {
                $cityIds[] = (int) $location->id;
            }
        }

        if ($cityIds === [] && $nearestId !== null) {
            $cityIds = [$nearestId];
        }

        sort($cityIds);

        return $cityIds;
    }
}
