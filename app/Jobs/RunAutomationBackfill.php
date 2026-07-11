<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Automation;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Applies one automation to its matching unanswered reviews ("Run now" on the
 * Automations page), optionally limited to a review-date window. Queued: a
 * backlog can mean hundreds of AI generations, which would blow through the
 * HTTP request timeout and provider rate limits if run inline.
 */
class RunAutomationBackfill implements ShouldQueue
{
    use Queueable;

    /** One attempt: generations debit AI usage, so a retry could double-spend. */
    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public readonly string $workspaceId,
        public readonly int $automationId,
        public readonly ?string $from = null,
        public readonly ?string $until = null,
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
            $automation = Automation::find($this->automationId);
            if ($automation === null) {
                return;
            }

            $stats = $service->processAutomation(
                $workspace,
                $automation,
                $this->from !== null ? CarbonImmutable::parse($this->from)->startOfDay() : null,
                $this->until !== null ? CarbonImmutable::parse($this->until)->endOfDay() : null,
            );

            Log::info('Automation backfill finished', [
                'workspace' => $this->workspaceId,
                'automation' => $automation->name,
            ] + $stats);

            // Drafts routed to the approval queue → nudge the owner (throttled).
            if ($stats['queued'] > 0) {
                $service->notifyPendingApprovals($workspace);
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
