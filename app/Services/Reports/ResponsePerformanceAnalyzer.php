<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Review;
use App\Support\DashboardPeriod;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Response performance" analysis: how well are reviews being replied to?
 * Reply rate, how many are still unanswered, and how fast replies go out
 * (using replied_at − created_at_external). Pure computation, no AI.
 */
class ResponsePerformanceAnalyzer
{
    /**
     * @return array{
     *   total: int, replied: int, unanswered: int, rate: int,
     *   avgResponseHours: float|null, within24hPct: int|null
     * }
     */
    public function analyze(DashboardPeriod $period): array
    {
        $reviews = $this->window($period)->get(['created_at_external', 'replied_at']);

        $total = $reviews->count();
        $repliedRows = $reviews->filter(fn (Review $r): bool => $r->replied_at !== null);
        $replied = $repliedRows->count();

        // Response times only where we know both timestamps.
        $hours = $repliedRows
            ->filter(fn (Review $r): bool => $r->created_at_external !== null)
            ->map(fn (Review $r): float => max(0, $r->created_at_external->floatDiffInHours($r->replied_at)));

        $within24 = $hours->filter(fn (float $h): bool => $h <= 24)->count();

        return [
            'total' => $total,
            'replied' => $replied,
            'unanswered' => max(0, $total - $replied),
            'rate' => $total > 0 ? (int) round($replied / $total * 100) : 0,
            'avgResponseHours' => $hours->isNotEmpty() ? round($hours->avg(), 1) : null,
            'within24hPct' => $hours->isNotEmpty() ? (int) round($within24 / $hours->count() * 100) : null,
        ];
    }

    private function window(DashboardPeriod $period): Builder
    {
        return Review::query()
            ->when($period->locationId, fn (Builder $q, int $id): Builder => $q->where('location_id', $id))
            ->whereBetween('created_at_external', [$period->start, $period->end]);
    }
}
