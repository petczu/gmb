<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Models\Location;
use App\Models\TrackedPlace;
use App\Models\Workspace;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Snapshots are CENTRAL and keyed by place_id, so every unique place costs
 * ONE Places API call per day no matter how many workspaces track it. The
 * fresh values are then fanned back out to each tenant's competitor rows,
 * and the admin watchlist (tracked_places) is included even when no tenant
 * tracks those places yet.
 */
class RefreshCompetitorsCommand extends Command
{
    protected $signature = 'competitors:refresh
        {workspace? : Workspace id or slug; omit for all}
        {--watchlist : Also refresh the admin watchlist (tracked_places) that no tenant uses}';

    protected $description = 'Refresh competitor ratings/review counts from the Google Places API';

    public function handle(PlacesClient $places): int
    {
        if (! $places->configured()) {
            $this->warn('GOOGLE_PLACES_API_KEY is not set — skipping.');

            return self::SUCCESS;
        }

        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        // Pass 1 — the distinct place ids across all tenants, plus a map of
        // place ids we ALREADY sync (connected locations with a resolved
        // place_id) → their fresh rating/reviews. Those get a snapshot from
        // our own data and skip the paid Places call entirely.
        //
        // The admin watchlist (tracked_places, potentially hundreds of rows
        // from bulk discovery) joins only with --watchlist: refreshing places
        // no tenant uses every day is what burns the Places budget.
        $placeIds = $this->option('watchlist')
            ? TrackedPlace::query()->pluck('place_id')->all()
            : [];

        /** @var array<string, array{name: ?string, address: ?string, rating: ?float, reviews_count: int}> $connected */
        $connected = [];

        foreach ($workspaces as $workspace) {
            $this->inTenant($workspace, function () use (&$placeIds, &$connected): void {
                $placeIds = array_merge($placeIds, Competitor::query()->pluck('place_id')->all());

                Location::query()
                    ->whereNotNull('place_id')->where('place_id', '!=', '')
                    ->get(['place_id', 'name', 'address', 'rating', 'reviews_count'])
                    ->each(function (Location $location) use (&$connected): void {
                        $connected[(string) $location->place_id] = [
                            'name' => $location->name,
                            'address' => $location->address,
                            'rating' => $location->rating !== null ? (float) $location->rating : null,
                            'reviews_count' => (int) $location->reviews_count,
                        ];
                    });
            });
        }

        // Pass 2 — ONE details call per place, one central snapshot per day.
        // Connected places are snapshotted from synced data (no Places call).
        /** @var array<string, array{name: ?string, address: ?string, rating: ?float, reviews_count: int}> $fresh */
        $fresh = [];
        $skipped = 0;

        foreach (array_values(array_unique($placeIds)) as $placeId) {
            if (isset($connected[$placeId])) {
                $fresh[$placeId] = $connected[$placeId];
                CompetitorTrends::recordPlace($placeId, $fresh[$placeId]['rating'], $fresh[$placeId]['reviews_count']);
                $skipped++;

                continue;
            }

            try {
                $details = $places->details($placeId);
                $fresh[$placeId] = [
                    'name' => $details['name'] ?? null,
                    'address' => $details['address'] ?? null,
                    'rating' => $details['rating'] !== null ? (float) $details['rating'] : null,
                    'reviews_count' => (int) ($details['reviews_count'] ?? 0),
                ];

                CompetitorTrends::recordPlace($placeId, $fresh[$placeId]['rating'], $fresh[$placeId]['reviews_count']);
            } catch (Throwable $e) {
                Log::warning('Competitor refresh failed', ['place' => $placeId, 'error' => $e->getMessage()]);
            }
        }

        // Pass 3 — fan the fresh values back out to each tenant's competitor rows.
        foreach ($workspaces as $workspace) {
            $this->inTenant($workspace, function () use ($fresh): void {
                foreach (Competitor::query()->get() as $competitor) {
                    $values = $fresh[$competitor->place_id] ?? null;

                    if ($values === null) {
                        continue;
                    }

                    $competitor->forceFill([
                        'name' => $values['name'] ?: $competitor->name,
                        'address' => $values['address'] ?? $competitor->address,
                        'rating' => $values['rating'],
                        'reviews_count' => $values['reviews_count'],
                        'last_checked_at' => now(),
                    ])->save();
                }
            });
        }

        $this->info(sprintf(
            'Competitors refreshed (%d unique places, %d from connected locations without a Places call).',
            count($fresh),
            $skipped,
        ));

        return self::SUCCESS;
    }

    private function inTenant(Workspace $workspace, callable $callback): void
    {
        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $callback();
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
