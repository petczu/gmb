<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs the matching automation for ONE freshly ingested review (dispatched by
 * the webhook handler on review.new). Queued so AI generation never delays the
 * webhook response; the scheduled automations:run pass is the safety net for
 * anything this misses.
 */
class RunReviewAutomation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> seconds */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly string $workspaceId,
        public readonly int $reviewId,
    ) {}

    public function handle(AutomationService $service): void
    {
        $workspace = Workspace::find($this->workspaceId);
        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $review = Review::find($this->reviewId);
            if ($review !== null) {
                $item = $service->processReview($workspace, $review);

                // A queued draft awaits approval → notify the owner (throttled).
                if ($item?->status === 'pending') {
                    $service->notifyPendingApprovals($workspace);
                }
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
