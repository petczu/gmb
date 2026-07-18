<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Location;
use Throwable;

/**
 * Resolves a connected location's Google Maps place_id via a Places Text
 * Search on its name + address. Zernio gives us the GBP location id
 * (Location.external_id), not the place_id the Places API uses, so this is the
 * bridge that lets competitors:refresh recognise a tracked place as one we
 * already sync and skip the paid Places call for it.
 *
 * Best-effort: a missing key, no match or an API error simply leaves place_id
 * null (the place is polled normally, nothing breaks).
 */
class LocationPlaceResolver
{
    public function __construct(private readonly PlacesClient $places) {}

    /** Resolve + store the place_id, returning it (or null when unresolved). */
    public function resolve(Location $location): ?string
    {
        if (! $this->places->configured() || filled($location->place_id)) {
            return $location->place_id;
        }

        $query = trim(($location->name ?? '').' '.($location->address ?? ''));
        if ($query === '') {
            return null;
        }

        try {
            $results = $this->places->search($query);
        } catch (Throwable) {
            return null;
        }

        $best = $this->pickBest($results, (string) $location->name);
        if ($best === null || ($best['place_id'] ?? '') === '') {
            return null;
        }

        // Also store coordinates so competitors can be auto-scoped to this
        // location's city by distance (best-effort; a failure leaves them null).
        $coords = null;
        try {
            $coords = $this->places->coordinates($best['place_id']);
        } catch (Throwable) {
            $coords = null;
        }

        $location->forceFill(array_filter([
            'place_id' => $best['place_id'],
            'latitude' => $coords['lat'] ?? null,
            'longitude' => $coords['lng'] ?? null,
        ], fn ($value): bool => $value !== null))->save();

        return $best['place_id'];
    }

    /**
     * Prefer a result whose name overlaps the location name (guards against a
     * wildly wrong top hit); otherwise trust the Places ranking for the
     * name+address query and take the first result.
     *
     * @param  list<array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}>  $results
     * @return array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}|null
     */
    private function pickBest(array $results, string $name): ?array
    {
        if ($results === []) {
            return null;
        }

        $target = mb_strtolower(trim($name));

        if ($target !== '') {
            foreach ($results as $result) {
                $candidate = mb_strtolower((string) ($result['name'] ?? ''));
                if ($candidate !== '' && (str_contains($candidate, $target) || str_contains($target, $candidate))) {
                    return $result;
                }
            }
        }

        return $results[0];
    }
}
