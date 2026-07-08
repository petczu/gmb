<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\ListLocations;
use App\Ai\Tools\ListReviews;
use App\Ai\Tools\ReviewStats;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * "Ask AI": answers natural-language questions over the CURRENT workspace's
 * review data via read-only tools. Must run inside initialized tenancy.
 * Conversation history is short-lived (the chat page passes it in).
 */
#[Provider(Lab::Anthropic)]
#[MaxSteps(8)]
#[MaxTokens(1500)]
#[Timeout(90)]
class WorkspaceAnalyst implements Agent, Conversational, HasTools
{
    use Promptable;

    /** @param list<array{role: string, content: string}> $history */
    public function __construct(protected array $history = []) {}

    public function instructions(): Stringable|string
    {
        return implode("\n", [
            'You are the analytics assistant inside Repunio, a Google-review management app. You answer questions about THIS workspace\'s locations and reviews using the provided read-only tools.',
            'Today is '.now()->toFormattedDateString().'. Use this date for anything relative ("this month", "last week").',
            'Always ground answers in tool results; quote concrete numbers, names and dates. If the data does not contain the answer, say so plainly.',
            'Answer in the language of the user\'s question. Be concise: a short paragraph or a compact list, no preamble.',
            'You cannot change anything (no replies, no settings) — if asked to act, explain where in the app to do it.',
            'Style: no em dashes, no marketing fluff, plain and concrete.',
        ]);
    }

    public function messages(): iterable
    {
        return array_map(
            fn (array $m): Message => new Message($m['role'], $m['content']),
            $this->history,
        );
    }

    public function tools(): iterable
    {
        return [
            new ListLocations,
            new ListReviews,
            new ReviewStats,
        ];
    }
}
