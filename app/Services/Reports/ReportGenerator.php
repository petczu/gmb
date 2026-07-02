<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Services\Billing\AiUsageService;
use App\Support\DashboardPeriod;
use Illuminate\Support\Facades\Cache;

/**
 * Coordinates the report's AI summary so tokens are only spent on an explicit
 * "Generate" action — never on preview/download. The cache key is derived from
 * the RESOLVED period (not raw filter strings) so the Reports page and the
 * controller hit the same entry.
 */
class ReportGenerator
{
    public function __construct(
        private readonly ReportInsights $insights,
        private readonly AiUsageService $usage,
        private readonly AiCreditService $credits,
    ) {}

    public function cacheKey(DashboardPeriod $period, string $language = 'en'): string
    {
        return 'report-insights:'.md5(implode('|', [
            $period->start->toDateString(),
            $period->end->toDateString(),
            $period->prevStart->toDateString(),
            $period->prevEnd->toDateString(),
            $period->locationId ?? 'all',
            $period->compare ? '1' : '0',
            $language,
            // Changed owner guidance must produce a fresh AI narrative.
            (string) ReportInsights::customInstructions(),
        ]));
    }

    /** Cached AI summary if one was generated, otherwise the free fallback. */
    public function cachedOrFallback(DashboardPeriod $period, array $report, string $language = 'en'): array
    {
        $insights = Cache::get($this->cacheKey($period, $language)) ?? $this->insights->fallbackFor($report);

        // Idempotent safety net for entries cached before the alias merge ran.
        $aliases = ReportInsights::parseAliasMap(ReportInsights::customInstructions());
        if (! empty($insights['staff']) && $aliases !== []) {
            $insights['staff'] = $this->insights->withShares(
                ReportInsights::mergeStaffByAliases($insights['staff'], $aliases),
            );
        }

        return $insights;
    }

    public function hasCached(DashboardPeriod $period, string $language = 'en'): bool
    {
        return Cache::has($this->cacheKey($period, $language));
    }

    /**
     * Generate the AI summary, cache it (6h) and count it against the plan's
     * monthly report allowance. Over the cap → basic (non-AI) summary, no spend.
     *
     * @return array{insights: array<string, mixed>, ai: bool}
     */
    public function generate(DashboardPeriod $period, array $report, Workspace $workspace, string $language = 'en'): array
    {
        if (! $this->usage->canGenerateReport($workspace)) {
            return ['insights' => $this->insights->fallbackFor($report), 'ai' => false];
        }

        $insights = $this->insights->generate($report, $language);
        Cache::put($this->cacheKey($period, $language), $insights, now()->addHours(6));

        // Log the report generation (reason='report' is what the monthly report
        // allowance counts). When the allowance is exhausted but credits cover it,
        // debit the report's credit cost on the same ledger row.
        $usage = $this->insights->lastUsage;
        $creditDelta = $this->usage->isReportServedFromCredits($workspace)
            ? -$this->usage->reportCredits()
            : 0;
        $this->credits->logUsage(
            $workspace,
            'report',
            $usage['model'] ?? null,
            (int) ($usage['input'] ?? 0),
            (int) ($usage['output'] ?? 0),
            $creditDelta,
            'report',
            substr(md5(uniqid('rpt', true)), 0, 32),
        );

        return ['insights' => $insights, 'ai' => true];
    }
}
