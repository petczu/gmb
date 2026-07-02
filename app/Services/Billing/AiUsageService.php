<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\AiLimitReachedMail;
use App\Models\AutoReplyQueueItem;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tracks AI auto-reply usage against the plan's monthly allowance. This is the
 * internal meter behind the tier limits (20 / 250 / 1500) — the customer never
 * sees "credits", only "X of Y AI replies used this month".
 *
 * Assumes tenant context (counts the current workspace's queue items).
 */
class AiUsageService
{
    public function __construct(
        private readonly LocationBilling $billing,
        private readonly AiCreditService $credits,
    ) {}

    /**
     * AI replies generated this calendar month — every actual AI generation
     * counts (auto-publish, pending approval, OR a manual generate in the reply
     * modal). `model` is only set when the AI really ran (skipped/failed = null).
     */
    public function autoRepliesThisMonth(): int
    {
        return AutoReplyQueueItem::query()
            ->whereNotNull('model')
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();
    }

    public function cap(Workspace $workspace): int
    {
        // When billing is off (local/dev), don't throttle.
        return $this->billing->enabled() ? $this->billing->aiReplyCap($workspace) : PHP_INT_MAX;
    }

    public function remaining(Workspace $workspace): int
    {
        return max(0, $this->cap($workspace) - $this->autoRepliesThisMonth());
    }

    /**
     * Can an AI reply be generated right now? True while the plan's monthly
     * allowance has room OR there are purchased top-up credits to fall back on.
     * Only when BOTH are exhausted do we hard-stop (current behavior).
     */
    public function canAutoReply(Workspace $workspace): bool
    {
        return $this->remaining($workspace) > 0
            || $this->credits->balance($workspace) > 0;
    }

    /**
     * Whether the NEXT AI reply will be served from the purchased credit balance
     * rather than the plan allowance — i.e. the plan is exhausted but credits
     * remain. Call this BEFORE logging usage to decide whether to debit a credit.
     */
    public function isServedFromCredits(Workspace $workspace): bool
    {
        return $this->remaining($workspace) <= 0
            && $this->credits->balance($workspace) > 0;
    }

    /**
     * Total AI replies still available this month: plan allowance left plus any
     * purchased credit balance. For UI hints only (e.g. "{N} left").
     */
    public function effectiveRemaining(Workspace $workspace): int
    {
        $planRemaining = $this->remaining($workspace);

        if ($planRemaining >= PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        return $planRemaining + max(0, $this->credits->balance($workspace));
    }

    /**
     * Email the workspace owner once per calendar month when the monthly AI
     * reply allowance has been hit. Best-effort: a mail failure must never abort
     * the automation/auto-reply run that triggered it.
     *
     * The dedupe flag (ai_limit_emailed_YYYY_MM) is a non-custom attribute, so
     * stancl persists it into the central tenants `data` JSON — safe to write
     * even while a tenant DB context is initialized.
     */
    public function notifyLimitReachedOnce(Workspace $workspace): void
    {
        // No caps (and so no "limit reached" emails) when billing is off.
        if (! $this->billing->enabled()) {
            return;
        }

        try {
            $flag = 'ai_limit_emailed_'.CarbonImmutable::now()->format('Y_m');

            if ($workspace->getAttribute($flag)) {
                return; // already emailed this month
            }

            // Set + persist the flag before sending so a transient mail failure
            // doesn't cause a retry storm within the same month.
            $workspace->setAttribute($flag, true);
            $workspace->save();

            $plan = $this->billing->plan($workspace)?->name ?? 'your current';
            $plansUrl = rtrim((string) config('app.url'), '/').'/billing';

            app(\App\Services\Notifications\NotificationDispatcher::class)->dispatch(
                $workspace,
                \App\Services\Notifications\NotificationCategory::BILLING,
                fn (string $name, string $lang) => new AiLimitReachedMail(
                    name: $name,
                    plan: $plan,
                    plansUrl: $plansUrl,
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('AI limit email failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** AI reports generated this calendar month (logged in the central ledger). */
    public function reportsThisMonth(Workspace $workspace): int
    {
        return \App\Models\AiCreditLedger::query()
            ->where('workspace_id', $workspace->id)
            ->where('reason', 'report')
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->count();
    }

    public function reportCap(Workspace $workspace): int
    {
        return $this->billing->enabled() ? $this->billing->reportCap($workspace) : PHP_INT_MAX;
    }

    /** Credits charged per AI report (default 5). */
    public function reportCredits(): int
    {
        return (int) config('services.ai.report_credits', 5);
    }

    /**
     * An AI report can be generated when the monthly report allowance has room
     * OR there are enough purchased credits to cover one report.
     */
    public function canGenerateReport(Workspace $workspace): bool
    {
        return $this->reportsThisMonth($workspace) < $this->reportCap($workspace)
            || $this->credits->balance($workspace) >= $this->reportCredits();
    }

    /**
     * Whether the NEXT AI report will be paid from purchased credits — i.e. the
     * monthly report allowance is exhausted but enough credits remain.
     */
    public function isReportServedFromCredits(Workspace $workspace): bool
    {
        return $this->reportsThisMonth($workspace) >= $this->reportCap($workspace)
            && $this->credits->balance($workspace) >= $this->reportCredits();
    }

    /** Total real USD cost of AI calls this calendar month (from the ledger). */
    public function costThisMonth(Workspace $workspace): float
    {
        return (float) \App\Models\AiCreditLedger::query()
            ->where('workspace_id', $workspace->id)
            ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
            ->sum('cost_usd');
    }
}
