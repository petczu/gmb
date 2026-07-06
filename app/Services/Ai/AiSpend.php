<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiCreditLedger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * System-wide AI consumption aggregates over the central ai_credit_ledger
 * (every AI call is logged there with tokens + cost_usd). Powers the /admin
 * "AI usage" page and the global monthly budget alert.
 */
class AiSpend
{
    /** The global monthly USD budget, or null when not configured. */
    public function budget(): ?float
    {
        $budget = config('services.ai.monthly_budget_usd');

        return filled($budget) && (float) $budget > 0 ? (float) $budget : null;
    }

    public function monthSpendUsd(?CarbonImmutable $month = null): float
    {
        return (float) $this->monthQuery($month)->sum('cost_usd');
    }

    /**
     * Headline numbers for the admin page.
     *
     * @return array{this_month: float, last_month: float, total: float, calls: int, input_tokens: int, output_tokens: int}
     */
    public function stats(): array
    {
        $now = CarbonImmutable::now();

        $current = $this->monthQuery($now)
            ->selectRaw('COALESCE(SUM(cost_usd), 0) as cost, COUNT(*) as calls, COALESCE(SUM(input_tokens), 0) as input, COALESCE(SUM(output_tokens), 0) as output')
            ->first();

        return [
            'this_month' => (float) $current->cost,
            'last_month' => $this->monthSpendUsd($now->subMonthNoOverflow()),
            'total' => (float) AiCreditLedger::query()->sum('cost_usd'),
            'calls' => (int) $current->calls,
            'input_tokens' => (int) $current->input,
            'output_tokens' => (int) $current->output,
        ];
    }

    /**
     * This month's spend per workspace, most expensive first.
     *
     * @return Collection<int, object{workspace_id: string, cost: float, calls: int}>
     */
    public function byWorkspace(int $limit = 10): Collection
    {
        return $this->monthQuery()
            ->selectRaw('workspace_id, SUM(cost_usd) as cost, COUNT(*) as calls')
            ->groupBy('workspace_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object{reason: string, cost: float, calls: int}>
     */
    public function byReason(): Collection
    {
        return $this->monthQuery()
            ->selectRaw('reason, SUM(cost_usd) as cost, COUNT(*) as calls')
            ->groupBy('reason')
            ->orderByDesc('cost')
            ->get();
    }

    /**
     * @return Collection<int, object{model: ?string, cost: float, calls: int, input: int, output: int}>
     */
    public function byModel(): Collection
    {
        return $this->monthQuery()
            ->whereNotNull('model')
            ->selectRaw('model, SUM(cost_usd) as cost, COUNT(*) as calls, COALESCE(SUM(input_tokens), 0) as input, COALESCE(SUM(output_tokens), 0) as output')
            ->groupBy('model')
            ->orderByDesc('cost')
            ->get();
    }

    /**
     * Daily spend for the last N days (gaps filled with 0), oldest first.
     *
     * @return list<array{day: string, cost: float}>
     */
    public function byDay(int $days = 30): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);

        $rows = AiCreditLedger::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'cost_usd'])
            ->groupBy(fn (AiCreditLedger $row): string => $row->created_at->toDateString())
            ->map(fn (Collection $group): float => (float) $group->sum('cost_usd'));

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->addDays($i)->toDateString();
            $out[] = ['day' => $day, 'cost' => (float) ($rows[$day] ?? 0.0)];
        }

        return $out;
    }

    protected function monthQuery(?CarbonImmutable $month = null): Builder
    {
        $month ??= CarbonImmutable::now();

        return AiCreditLedger::query()
            ->where('created_at', '>=', $month->startOfMonth())
            ->where('created_at', '<=', $month->endOfMonth());
    }
}
