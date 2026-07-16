<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Location;
use App\Models\Workspace;
use App\Services\Competitors\LocationCidResolver;
use App\Services\Competitors\LocationPlaceResolver;
use App\Services\Competitors\LocationTimezoneResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Resolves a freshly connected location's Google Maps place_id off the request
 * (one Places Text Search), so competitors:refresh can later skip the paid
 * Places call for it, then detects its IANA timezone for auto-reply scheduling.
 * Best-effort: failure leaves place_id / timezone null.
 */
class ResolveLocationPlaceId implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly string $workspaceId,
        public readonly int $locationId,
    ) {}

    public function handle(LocationPlaceResolver $resolver, LocationTimezoneResolver $timezones, LocationCidResolver $cids): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $location = Location::find($this->locationId);
            if ($location !== null) {
                $resolver->resolve($location);
                $timezones->resolve($location);
                $cids->resolve($location);
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
