<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use App\Models\Location;
use Throwable;

/**
 * Detects a connected location's IANA timezone from Google: the place's
 * coordinates (Places) fed to the Time Zone API. Stored on the location and
 * used to interpret auto-reply working hours in the right local time.
 *
 * Best-effort: a missing key, unresolved place_id or API error simply leaves
 * the timezone null (scheduling falls back to the workspace timezone).
 */
class LocationTimezoneResolver
{
    public function __construct(private readonly PlacesClient $places) {}

    /** Resolve + store the timezone, returning it (or null when unresolved). */
    public function resolve(Location $location): ?string
    {
        if (! $this->places->configured() || filled($location->timezone) || blank($location->place_id)) {
            return $location->timezone;
        }

        try {
            $coordinates = $this->places->coordinates((string) $location->place_id);
            if ($coordinates === null) {
                return null;
            }

            $timezone = $this->places->timezoneAt($coordinates['lat'], $coordinates['lng']);
        } catch (Throwable) {
            return null;
        }

        if ($timezone === null) {
            return null;
        }

        $location->forceFill(['timezone' => $timezone])->save();

        return $timezone;
    }
}
