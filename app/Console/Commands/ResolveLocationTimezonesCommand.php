<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Workspace;
use App\Services\Competitors\LocationCidResolver;
use App\Services\Competitors\LocationTimezoneResolver;
use Illuminate\Console\Command;

/**
 * Backfills IANA timezones for already-connected locations (Google Places +
 * Time Zone API). New locations get theirs on connect; this catches up the
 * ones that predate the feature. Best-effort per location.
 */
class ResolveLocationTimezonesCommand extends Command
{
    protected $signature = 'locations:resolve-timezones
        {workspace? : Workspace id or slug; omit for all}
        {--force : Re-resolve even locations that already have a timezone}';

    protected $description = 'Detect and store the IANA timezone and Google CID for connected locations';

    public function handle(LocationTimezoneResolver $resolver, LocationCidResolver $cids): int
    {
        $arg = $this->argument('workspace');
        $workspaces = $arg !== null
            ? Workspace::query()->where('id', $arg)->orWhere('slug', $arg)->get()
            : Workspace::query()->get();

        if ($workspaces->isEmpty()) {
            $this->warn('No matching workspaces.');

            return self::SUCCESS;
        }

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $tz = 0;
                $cid = 0;
                Location::query()->whereNotNull('place_id')->get()->each(function (Location $location) use ($resolver, $cids, &$tz, &$cid): void {
                    if ($this->option('force')) {
                        $location->forceFill(['timezone' => null, 'cid' => null])->save();
                    }

                    if (filled($resolver->resolve($location))) {
                        $tz++;
                    }
                    if (filled($cids->resolve($location))) {
                        $cid++;
                    }
                });

                $this->line("[{$workspace->slug}] timezones: {$tz}  cids: {$cid}");
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }
}
