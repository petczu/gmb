<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

/**
 * Generates a country's marketing calendar for the AI-created external
 * calendars ([[HolidayCalendarSync]]). Structured output keeps the dates
 * machine-usable; the model is picked per-prompt from services.ai.model.
 */
#[Provider(Lab::Anthropic)]
#[MaxTokens(8192)]
#[MaxSteps(12)]
#[Temperature(0.2)]
#[Timeout(300)]
class HolidayPlanner implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Web search lets the model VERIFY dates instead of recalling them —
     * fixed national days (e.g. Emirati Women's Day = Aug 28) and movable
     * religious dates are exactly where pure recall gets dates wrong.
     */
    public function tools(): iterable
    {
        return [(new WebSearch)->max(8)];
    }

    public function instructions(): string
    {
        return implode("\n", [
            'You are a meticulous marketing-calendar researcher for local businesses.',
            'Given a country or region, a date window and a list of requested categories, produce EVERY relevant day in that window:',
            '- official: official public holidays (days off).',
            '- religious: cultural and religious observances (Ramadan start, Easter, Diwali, Chinese New Year...), even when not a day off.',
            '- awareness: national and awareness days locals know and brands post about (e.g. Emirati Women\'s Day, UAE Flag Day, Mother\'s/Father\'s Day, Valentine\'s Day, International Women\'s Day, Earth Day...).',
            '- shopping: retail and marketing moments (Black Friday / White Friday, Cyber Monday, Singles\' Day, back-to-school season, end-of-season sales...).',
            'Method: go through the window MONTH BY MONTH and list the days for each month before moving on; minor but locally known days matter as much as big ones. Aim for completeness over brevity.',
            'Verification: use web search to confirm any date you are not fully certain of — especially fixed national/awareness days and movable religious dates for the requested years. A wrong date is worse than a missing entry.',
            'Rules:',
            '- Only real, verifiable observances relevant to that market. No invented days. When a movable religious date is approximate, still give your best single date.',
            '- One entry per observance (same-day observances may be separate entries).',
            '- `date` is YYYY-MM-DD within the window. `title` is short (max 60 chars), in English with the local name in parentheses when it differs.',
            '- Include ONLY the requested categories.',
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'events' => $schema->array()
                ->items($schema->object(fn (JsonSchema $schema): array => [
                    'date' => $schema->string()->required(),
                    'title' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}
