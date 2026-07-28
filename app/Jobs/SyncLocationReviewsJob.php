<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Location;
use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Str;
use Throwable;

/**
 * Syncs ONE location's reviews. The workspace-level jobs fan these out (one per
 * location, staggered) so a workspace with many locations spreads its Zernio
 * calls over time instead of hammering the API in a single long job — which
 * tripped rate limits and blew the worker timeout.
 */
class SyncLocationReviewsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> seconds */
    public array $backoff = [30, 120, 300];

    /** One location's reviews fit comfortably; kept below redis retry_after. */
    public int $timeout = 240;

    /** No duplicate in-flight sync for the same location. */
    public int $uniqueFor = 600;

    /** Seconds between consecutive per-location dispatches (Zernio throttle). */
    public const STAGGER_SECONDS = 12;

    public function __construct(public readonly string $workspaceId, public readonly int $locationId) {}

    public function uniqueId(): string
    {
        return $this->workspaceId.':'.$this->locationId;
    }

    /**
     * Fan out one staggered per-location sync job for every tracked location of
     * the workspace. Returns the number dispatched (0 if the workspace is gone).
     */
    public static function fanOutForWorkspace(string $workspaceId): int
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace === null) {
            return 0;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $locationIds = Location::query()->orderBy('id')->pluck('id')->all();
        } finally {
            $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
        }

        foreach (array_values($locationIds) as $i => $id) {
            self::dispatch($workspaceId, (int) $id)
                ->delay(now()->addSeconds($i * self::STAGGER_SECONDS));
        }

        return count($locationIds);
    }

    public function handle(ReviewSync $sync): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        $location = null;
        $result = null;

        try {
            $location = Location::find($this->locationId);

            if ($location !== null) {
                $result = $sync->syncLocation($workspace, $location, tenancyManaged: false);
            }
        } finally {
            $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
        }

        // Notifications run with the central context restored.
        if ($location !== null && $result !== null) {
            $sync->notifyLocationResult($workspace, $location, $result);
        }
    }

    /**
     * Final failure (all tries exhausted, timeout kill, …). handle() records
     * provider errors itself, but a timeout death leaves no trace on the model
     * — flag the location so the UI shows the sync is failing, and translate
     * the queue-internal exceptions into something a human can read.
     */
    public function failed(?Throwable $exception): void
    {
        $workspace = Workspace::find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $message = match (true) {
            $exception instanceof MaxAttemptsExceededException,
            $exception instanceof TimeoutExceededException => 'Review sync timed out repeatedly; it will retry on the next scheduled sync.',
            $exception !== null => $exception->getMessage(),
            default => 'Review sync failed.',
        };

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            Location::query()
                ->whereKey($this->locationId)
                ->update(['last_sync_error' => Str::limit($message, 500)]);
        } finally {
            $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
