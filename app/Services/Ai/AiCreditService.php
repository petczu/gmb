<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Exceptions\InsufficientCreditsException;
use App\Models\AiCreditLedger;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Central AI-credit accounting. Balance is SUM(delta) over the append-only
 * ai_credit_ledger; every mutation is recorded as a new row.
 */
class AiCreditService
{
    private const CONNECTION = 'mysql';

    public function balance(Workspace $workspace): int
    {
        return (int) AiCreditLedger::query()->where('workspace_id', $workspace->id)->sum('delta');
    }

    /** Purchased credits spent (debited) this calendar month. */
    public function spentThisMonth(Workspace $workspace): int
    {
        $delta = (int) AiCreditLedger::query()
            ->where('workspace_id', $workspace->id)
            ->where('delta', '<', 0)
            ->where('created_at', '>=', \Carbon\CarbonImmutable::now()->startOfMonth())
            ->sum('delta');

        return abs($delta);
    }

    public function credit(Workspace $workspace, int $amount, string $reason = 'topup', ?string $refType = null, ?string $refId = null): AiCreditLedger
    {
        return $this->record($workspace, abs($amount), $reason, $refType, $refId);
    }

    /**
     * Grant a purchased AI-reply pack to the workspace balance. Idempotent on the
     * Stripe checkout session id ($stripeRef): Stripe re-delivers webhooks, so a
     * second call with the same ref is a no-op and returns the existing row. The
     * balance check + insert run under a row lock on the central connection so
     * concurrent deliveries can't double-grant.
     */
    public function creditPack(Workspace $workspace, int $credits, string $stripeRef): AiCreditLedger
    {
        return DB::connection(self::CONNECTION)->transaction(function () use ($workspace, $credits, $stripeRef): AiCreditLedger {
            $existing = AiCreditLedger::query()
                ->where('workspace_id', $workspace->id)
                ->where('reason', 'pack')
                ->where('ref_type', 'stripe_checkout')
                ->where('ref_id', $stripeRef)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $this->record($workspace, abs($credits), 'pack', 'stripe_checkout', $stripeRef);
        });
    }

    /**
     * @throws InsufficientCreditsException
     */
    public function debit(Workspace $workspace, int $amount, string $reason, ?string $refType = null, ?string $refId = null): AiCreditLedger
    {
        $amount = abs($amount);

        return DB::connection(self::CONNECTION)->transaction(function () use ($workspace, $amount, $reason, $refType, $refId) {
            $balance = (int) AiCreditLedger::query()
                ->where('workspace_id', $workspace->id)
                ->lockForUpdate()
                ->sum('delta');

            if ($balance < $amount) {
                throw new InsufficientCreditsException($balance, $amount);
            }

            return $this->record($workspace, -$amount, $reason, $refType, $refId, $balance - $amount);
        });
    }

    public function hasCredits(Workspace $workspace, int $amount): bool
    {
        return $this->balance($workspace) >= abs($amount);
    }

    /**
     * Record one AI call on the ledger with its real cost: model, token counts
     * and computed USD. The unified usage log for every AI operation (replies +
     * reports). `creditDelta` defaults to 0 (usage included in the plan); pass a
     * negative value to also debit paid credits (future overage).
     */
    public function logUsage(
        Workspace $workspace,
        string $reason,
        ?string $model,
        int $inputTokens,
        int $outputTokens,
        int $creditDelta = 0,
        ?string $refType = null,
        ?string $refId = null,
    ): AiCreditLedger {
        return AiCreditLedger::create([
            'workspace_id' => $workspace->id,
            'delta' => $creditDelta,
            'balance_after' => $this->balance($workspace) + $creditDelta,
            'reason' => $reason,
            'model' => $model,
            'input_tokens' => max(0, $inputTokens),
            'output_tokens' => max(0, $outputTokens),
            'cost_usd' => AiCost::usd($model, $inputTokens, $outputTokens),
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    private function record(Workspace $workspace, int $delta, string $reason, ?string $refType, ?string $refId, ?int $balanceAfter = null): AiCreditLedger
    {
        $balanceAfter ??= $this->balance($workspace) + $delta;

        return AiCreditLedger::create([
            'workspace_id' => $workspace->id,
            'delta' => $delta,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }
}
