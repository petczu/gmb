<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\ExternalCalendar;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * AI-generated marketing calendars: an ExternalCalendar whose "url" is the
 * ai://holidays/{country}?sets=... marker instead of an ICS feed. Generation
 * asks [[HolidayPlanner]] for the chosen categories of days and materializes
 * them as external_calendar_events, exactly like an ICS sync, so the calendar
 * grid needs no special handling. Refreshing re-generates with the same
 * country and categories.
 */
class HolidayCalendarSync
{
    public const URL_PREFIX = 'ai://holidays/';

    /** Category keys the user can pick on creation (order = prompt order). */
    public const SETS = ['official', 'religious', 'awareness', 'shopping'];

    /** Default window length when the marker carries no explicit range. */
    private const DEFAULT_MONTHS = 12;

    public static function isAiCalendar(ExternalCalendar $calendar): bool
    {
        return str_starts_with((string) $calendar->url, self::URL_PREFIX);
    }

    /**
     * @param  array<int, string>  $sets
     */
    public static function urlFor(string $country, array $sets = [], ?string $from = null, ?string $to = null): string
    {
        $sets = array_values(array_intersect(self::SETS, $sets)) ?: self::SETS;

        $query = ['sets' => implode(',', $sets)];
        if (filled($from)) {
            $query['from'] = $from;
        }
        if (filled($to)) {
            $query['to'] = $to;
        }

        return self::URL_PREFIX.rawurlencode(trim($country)).'?'.http_build_query($query);
    }

    public static function countryOf(ExternalCalendar $calendar): string
    {
        $path = (string) substr((string) $calendar->url, strlen(self::URL_PREFIX));

        return rawurldecode((string) strtok($path, '?'));
    }

    /**
     * The categories this calendar was created with (all for legacy markers).
     *
     * @return array<int, string>
     */
    public static function setsOf(ExternalCalendar $calendar): array
    {
        parse_str((string) parse_url((string) $calendar->url, PHP_URL_QUERY), $query);
        $sets = array_filter(explode(',', (string) ($query['sets'] ?? '')));

        return array_values(array_intersect(self::SETS, $sets)) ?: self::SETS;
    }

    /**
     * The generation window stored in the marker, defaulting to one year
     * from the current month when absent (legacy markers).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function windowOf(ExternalCalendar $calendar): array
    {
        parse_str((string) parse_url((string) $calendar->url, PHP_URL_QUERY), $query);

        $parse = function (mixed $value): ?CarbonImmutable {
            try {
                return filled($value) && is_string($value) ? CarbonImmutable::parse($value) : null;
            } catch (Throwable) {
                return null;
            }
        };

        $from = $parse($query['from'] ?? null) ?? CarbonImmutable::now()->startOfMonth();
        $to = $parse($query['to'] ?? null) ?? $from->addMonths(self::DEFAULT_MONTHS);

        return [$from, $to->lessThan($from) ? $from->addMonths(self::DEFAULT_MONTHS) : $to];
    }

    public function sync(ExternalCalendar $calendar): bool
    {
        [$from, $to] = self::windowOf($calendar);

        try {
            $events = $this->generateEvents($calendar, $from, $to);
        } catch (Throwable $e) {
            report($e);
            $calendar->forceFill(['sync_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return false;
        }

        $calendar->events()->delete();
        $calendar->events()->createMany($events->all());
        $calendar->forceFill(['synced_at' => now(), 'sync_error' => null])->save();

        return true;
    }

    /**
     * Fill the not-yet-covered tail of the marker window: generates ONLY the
     * months after the last stored event (existing events stay untouched).
     * Callers extend a calendar by moving the marker's `to` forward first.
     */
    public function extend(ExternalCalendar $calendar): bool
    {
        [$from, $to] = self::windowOf($calendar);

        $lastCovered = $calendar->events()->max('date');
        $start = $lastCovered === null ? $from : CarbonImmutable::parse((string) $lastCovered)->addDay();

        if ($start->greaterThan($to)) {
            $calendar->forceFill(['synced_at' => now(), 'sync_error' => null])->save();

            return true;
        }

        try {
            $events = $this->generateEvents($calendar, $start, $to);
        } catch (Throwable $e) {
            report($e);
            $calendar->forceFill(['sync_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return false;
        }

        // Append only days the calendar does not have yet.
        $existing = $calendar->events()->pluck('date')->map(fn ($d) => $d->toDateString())->all();
        $calendar->events()->createMany($events->reject(fn (array $e): bool => in_array($e['date'], $existing, true))->all());

        $calendar->forceFill(['synced_at' => now(), 'sync_error' => null])->save();

        return true;
    }

    /**
     * Ask the planner for one window's days, cleaned and one row per date.
     *
     * @return Collection<int, array{date: string, title: string}>
     */
    private function generateEvents(ExternalCalendar $calendar, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $country = self::countryOf($calendar);
        $sets = self::setsOf($calendar);

        $response = (new HolidayPlanner)->prompt(
            sprintf(
                'Country/region: %s. Window: %s to %s (inclusive). Requested categories: %s. Go month by month and list every matching day.',
                $country,
                $from->toDateString(),
                $to->toDateString(),
                implode(', ', $sets),
            ),
            model: (string) config('services.ai.model', 'claude-sonnet-4-6'),
        );

        // Visible in the super-admin AI usage ledger like every other AI call.
        if (($workspace = tenant()) instanceof Workspace) {
            app(AiCreditService::class)->logUsage(
                $workspace,
                'holiday_calendar',
                (string) config('services.ai.model', 'claude-sonnet-4-6'),
                (int) ($response->usage->promptTokens ?? 0),
                (int) ($response->usage->completionTokens ?? 0),
                0,
                'external_calendar',
                (string) $calendar->id,
            );
        }

        $events = collect($response['events'] ?? [])
            ->filter(fn ($e): bool => is_array($e) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($e['date'] ?? '')) === 1 && filled($e['title'] ?? null))
            ->map(fn (array $e): array => [
                'date' => (string) $e['date'],
                // Models sometimes tag titles with the category ("[awareness]").
                'title' => trim((string) preg_replace('/\s*\[[a-z ]+\]\s*$/i', '', (string) $e['title'])),
            ])
            ->filter(fn (array $e): bool => $e['date'] >= $from->toDateString() && $e['date'] <= $to->toDateString())
            // Several observances can share a day; the grid stores one
            // event row per (calendar, day), so join their titles.
            ->groupBy('date')
            ->map(fn ($group, string $date): array => [
                'date' => $date,
                'title' => mb_substr($group->pluck('title')->unique()->implode(' / '), 0, 120),
            ])
            ->sortKeys()
            ->values();

        if ($events->isEmpty()) {
            throw new \RuntimeException('The AI returned no days for "'.$country.'".');
        }

        return $events;
    }
}
