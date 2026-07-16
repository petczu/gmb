<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\LocationGroup;
use Carbon\CarbonImmutable;

/**
 * Resolves the dashboard's filter form values into a concrete date range, the
 * matching "previous period" (same length, immediately preceding) for
 * comparisons, plus the optional location filter. Shared by every dashboard
 * widget so they all read the same window.
 */
class DashboardPeriod
{
    /**
     * @param  ?int  $locationId  single-location filter (report pages); null = all
     * @param  list<int>  $locationIds  multi-location filter (dashboard); empty = all
     */
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $prevStart,
        public readonly CarbonImmutable $prevEnd,
        public readonly bool $compare,
        public readonly ?int $locationId,
        public readonly string $preset,
        public readonly array $locationIds = [],
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters  the dashboard page filters
     */
    public static function fromFilters(?array $filters): self
    {
        $filters ??= [];
        $preset = $filters['period'] ?? 'last_30';
        $now = CarbonImmutable::now();

        [$start, $end] = match ($preset) {
            'last_7' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'last_90' => [$now->subDays(89)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            'custom' => [
                isset($filters['startDate']) && $filters['startDate']
                    ? CarbonImmutable::parse($filters['startDate'])->startOfDay()
                    : $now->subDays(29)->startOfDay(),
                isset($filters['endDate']) && $filters['endDate']
                    ? CarbonImmutable::parse($filters['endDate'])->endOfDay()
                    : $now->endOfDay(),
            ],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()], // last_30
        };

        // Guard against an inverted custom range.
        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        // Comparison mode: 'none' | 'previous' | 'custom'. A plain boolean
        // `compare` (dashboard toggle) maps to 'previous'/'none'.
        $compareMode = $filters['compareMode'] ?? (! empty($filters['compare']) ? 'previous' : 'none');

        if ($compareMode === 'custom') {
            $prevStart = isset($filters['compareStartDate']) && $filters['compareStartDate']
                ? CarbonImmutable::parse($filters['compareStartDate'])->startOfDay()
                : $start->subSeconds(max(1, $start->diffInSeconds($end)));
            $prevEnd = isset($filters['compareEndDate']) && $filters['compareEndDate']
                ? CarbonImmutable::parse($filters['compareEndDate'])->endOfDay()
                : $start;

            if ($prevEnd->lessThan($prevStart)) {
                [$prevStart, $prevEnd] = [$prevEnd->startOfDay(), $prevStart->endOfDay()];
            }
        } else {
            // Previous period: same length, ending where the current one starts.
            $lengthSeconds = max(1, $start->diffInSeconds($end));
            $prevEnd = $start;
            $prevStart = $start->subSeconds($lengthSeconds);
        }

        // The dashboard sends an array (multi-select), report pages a scalar.
        // Normalize both into a list of ids; a single id also fills locationId
        // for consumers that only support one location. A "g:{id}" token is a
        // location group — expand it to its member location ids.
        $locationIds = self::expandLocationFilter((array) ($filters['location_id'] ?? []));

        return new self(
            start: $start,
            end: $end,
            prevStart: $prevStart,
            prevEnd: $prevEnd,
            compare: $compareMode !== 'none',
            locationId: count($locationIds) === 1 ? $locationIds[0] : null,
            preset: (string) $preset,
            locationIds: $locationIds,
        );
    }

    /**
     * Expand a raw location filter (integer location ids mixed with "g:{id}"
     * group tokens) into a unique list of location ids.
     *
     * @param  array<int, mixed>  $values
     * @return list<int>
     */
    private static function expandLocationFilter(array $values): array
    {
        $ids = [];
        $groupIds = [];

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_string($value) && str_starts_with($value, 'g:')) {
                $groupIds[] = (int) substr($value, 2);

                continue;
            }
            $ids[] = (int) $value;
        }

        if ($groupIds !== [] && tenancy()->initialized) {
            foreach (LocationGroup::query()->whereIn('id', $groupIds)->get() as $group) {
                $ids = array_merge($ids, $group->locationIds());
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** Whole days in the current window (inclusive), for adaptive bucketing/labels. */
    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function label(): string
    {
        return $this->start->format('M j, Y').' to '.$this->end->format('M j, Y');
    }

    public function previousLabel(): string
    {
        return $this->prevStart->format('M j, Y').' to '.$this->prevEnd->format('M j, Y');
    }
}
