<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\ExternalCalendarEvent;
use App\Models\HolidayBrief;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Support\Locales;
use Laravel\Ai\Enums\Lab;

use function Laravel\Ai\agent;

/**
 * Writes (once) and reads the platform-wide holiday explainer for a calendar
 * event: what the day is plus post ideas, in the requested language. Briefs
 * live in the CENTRAL holiday_briefs table keyed by a hash of
 * (country, date, title, locale) — the first workspace to ask pays the AI
 * call, every other tenant reads the stored text.
 */
class HolidayBriefWriter
{
    public function hashFor(ExternalCalendarEvent $event, string $locale): string
    {
        return sha1(implode('|', [
            mb_strtolower(trim($this->countryOf($event))),
            $event->date->toDateString(),
            mb_strtolower(trim((string) $event->title)),
            $locale,
        ]));
    }

    public function stored(ExternalCalendarEvent $event, string $locale): ?string
    {
        return HolidayBrief::query()->where('key_hash', $this->hashFor($event, $locale))->value('brief');
    }

    /** Generate and store the brief (no-op when it already exists). */
    public function ensure(ExternalCalendarEvent $event, string $locale): string
    {
        $existing = $this->stored($event, $locale);
        if ($existing !== null) {
            return $existing;
        }

        $language = Locales::ALL[$locale]['name'] ?? 'English';
        $country = $this->countryOf($event);

        $response = agent(instructions: implode("\n", [
            'You explain calendar days to local-business owners planning Google Business posts.',
            "Answer in {$language}, plain text, no markdown, under 120 words, in two short paragraphs:",
            '1) What this day is, who observes it and why it matters'.($country !== '' ? ' in '.$country : '').'.',
            '2) One or two concrete Google-post ideas a local business could publish for it.',
        ]))->prompt(
            sprintf('Day: %s on %s%s.', (string) $event->title, $event->date->toDateString(), $country !== '' ? ' ('.$country.')' : ''),
            provider: Lab::Anthropic,
            model: (string) config('services.ai.model', 'claude-sonnet-4-6'),
        );

        $text = trim((string) $response->text);

        if (($workspace = tenant()) instanceof Workspace) {
            app(AiCreditService::class)->logUsage(
                $workspace,
                'holiday_brief',
                (string) config('services.ai.model', 'claude-sonnet-4-6'),
                (int) ($response->usage->promptTokens ?? 0),
                (int) ($response->usage->completionTokens ?? 0),
            );
        }

        HolidayBrief::query()->firstOrCreate(['key_hash' => $this->hashFor($event, $locale)], [
            'country' => mb_substr($country, 0, 120),
            'date' => $event->date->toDateString(),
            'title' => mb_substr((string) $event->title, 0, 160),
            'locale' => $locale,
            'brief' => $text,
        ]);

        return $text;
    }

    public function countryOf(ExternalCalendarEvent $event): string
    {
        return $event->calendar && HolidayCalendarSync::isAiCalendar($event->calendar)
            ? HolidayCalendarSync::countryOf($event->calendar)
            : '';
    }

    /** Cache key marking a recent failed generation (polling shows the error). */
    public function failureKey(ExternalCalendarEvent $event, string $locale): string
    {
        return 'holiday-brief-failed:'.$this->hashFor($event, $locale);
    }
}
