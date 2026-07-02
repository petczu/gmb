<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Review;
use App\Support\DashboardPeriod;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ReviewStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getStats(): array
    {
        $period = DashboardPeriod::fromFilters($this->pageFilters);

        // Single query for current-window aggregates.
        $cur = $this->scoped($period)
            ->whereBetween('created_at_external', [$period->start, $period->end])
            ->selectRaw('count(*) as total, avg(rating) as avg_rating, sum(reply_text is not null) as replied_count')
            ->first();

        $count = (int) $cur->total;
        $avg = (float) ($cur->avg_rating ?? 0);
        $replied = (int) $cur->replied_count;
        $rate = $count > 0 ? (int) round($replied / $count * 100) : 0;

        // Unanswered "now" is workspace state, not period-scoped, always actionable.
        $unansweredNow = $this->scoped($period)->whereNull('reply_text')->count();

        // Previous window (for comparison) — single query.
        $pCount = $pAvg = $pRate = null;
        if ($period->compare) {
            $prev = $this->scoped($period)
                ->whereBetween('created_at_external', [$period->prevStart, $period->prevEnd])
                ->selectRaw('count(*) as total, avg(rating) as avg_rating, sum(reply_text is not null) as replied_count')
                ->first();

            $pCount = (int) $prev->total;
            $pAvg = $prev->avg_rating;
            $pReplied = (int) $prev->replied_count;
            $pRate = $pCount > 0 ? (int) round($pReplied / $pCount * 100) : 0;
        }

        return [
            $this->stat(__('widgets.average_rating'), number_format($avg, 2), $period->compare && $pAvg !== null
                ? round($avg - (float) $pAvg, 2) : null, __('widgets.average_rating_desc'), higherIsBetter: true),

            $this->stat(__('widgets.reviews_received'), (string) $count, $period->compare && $pCount !== null
                ? $count - $pCount : null, $period->label(), higherIsBetter: true, icon: 'heroicon-m-chat-bubble-left-right'),

            $this->stat(__('widgets.response_rate'), $rate.'%', $period->compare && $pRate !== null
                ? $rate - $pRate : null, __('widgets.replied_of', ['replied' => $replied, 'total' => $count]), higherIsBetter: true, unit: 'pp'),

            Stat::make(__('widgets.unanswered_now'), (string) $unansweredNow)
                ->description($unansweredNow > 0 ? __('widgets.awaiting_reply') : __('widgets.all_caught_up'))
                ->descriptionIcon($unansweredNow > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($unansweredNow > 0 ? 'warning' : 'success'),
        ];
    }

    /** Base query scoped to the selected location (if any). */
    protected function scoped(DashboardPeriod $period): Builder
    {
        return Review::query()
            ->when($period->locationId, fn (Builder $q, int $id): Builder => $q->where('location_id', $id));
    }

    private function stat(string $label, string $value, int|float|null $delta, string $fallback, bool $higherIsBetter, string $unit = '', ?string $icon = null): Stat
    {
        $stat = Stat::make($label, $value);

        if ($delta !== null && abs((float) $delta) >= ($unit === 'pp' ? 1 : 0.01)) {
            $up = $delta > 0;
            $good = $higherIsBetter ? $up : ! $up;
            $stat->description(__('widgets.vs_previous', ['delta' => ($up ? '+' : '').$delta.($unit ? ' '.$unit : '')]))
                ->descriptionIcon($up ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($good ? 'success' : 'danger');
        } else {
            $stat->description($fallback)->color('gray');
            if ($icon) {
                $stat->descriptionIcon($icon);
            }
        }

        return $stat;
    }
}
