<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Location;
use Throwable;

/**
 * Detects a location's Google Maps CID from its place_id (via the place's
 * googleMapsUri). Stored on the location so imported external posts, which
 * carry a CID in their URL, can be attributed to the right location when
 * several locations share one Zernio account.
 *
 * Best-effort: a missing key, unresolved place_id or API error leaves the CID
 * null (the post falls back to the account's first location).
 */
class LocationCidResolver
{
    public function __construct(private readonly PlacesClient $places) {}

    /** Resolve + store the CID, returning it (or null when unresolved). */
    public function resolve(Location $location): ?string
    {
        if (! $this->places->configured() || filled($location->cid) || blank($location->place_id)) {
            return $location->cid;
        }

        try {
            $cid = $this->places->mapsCid((string) $location->place_id);
        } catch (Throwable) {
            return null;
        }

        if ($cid === null) {
            return null;
        }

        $location->forceFill(['cid' => $cid])->save();

        return $cid;
    }
}
