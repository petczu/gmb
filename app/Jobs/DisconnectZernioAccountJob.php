<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Reviews\ZernioConnectionManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Removes a Google account on Zernio (and its central GoogleAccount row) after
 * its last location was disconnected. Queued because the Zernio API call can be
 * slow and must not block the disconnect action in the UI. Central context —
 * no tenancy needed (GoogleAccount is central).
 */
class DisconnectZernioAccountJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> seconds */
    public array $backoff = [30, 120];

    public function __construct(public readonly string $workspaceId, public readonly string $accountId) {}

    public function handle(ZernioConnectionManager $manager): void
    {
        $manager->disconnectAccount($this->workspaceId, $this->accountId);
    }
}
