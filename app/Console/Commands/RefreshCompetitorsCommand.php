<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Competitor;
use App\Models\Workspace;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshCompetitorsCommand extends Command
{
    protected $signature = 'competitors:refresh {workspace? : Workspace id or slug; omit for all}';

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

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                foreach (Competitor::query()->get() as $competitor) {
                    try {
                        $fresh = $places->details($competitor->place_id);

                        $competitor->forceFill([
                            'name' => $fresh['name'] ?: $competitor->name,
                            'address' => $fresh['address'] ?? $competitor->address,
                            'rating' => $fresh['rating'],
                            'reviews_count' => $fresh['reviews_count'],
                            'last_checked_at' => now(),
                        ])->save();

                        app(CompetitorTrends::class)->record($competitor);
                    } catch (Throwable $e) {
                        Log::warning('Competitor refresh failed', [
                            'workspace' => $workspace->id,
                            'competitor' => $competitor->place_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        $this->info('Competitors refreshed.');

        return self::SUCCESS;
    }
}
