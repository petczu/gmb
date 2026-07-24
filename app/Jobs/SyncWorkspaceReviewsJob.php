<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatcher: fans out one staggered {@see SyncLocationReviewsJob} per tracked
 * location so a workspace's Zernio calls spread over time instead of running in
 * one long job (which tripped rate limits and the worker timeout). Lightweight —
 * it only reads the location ids and queues the per-location jobs.
 */
class SyncWorkspaceReviewsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public readonly string $workspaceId) {}

    public function handle(): void
    {
        SyncLocationReviewsJob::fanOutForWorkspace($this->workspaceId);
    }
}
