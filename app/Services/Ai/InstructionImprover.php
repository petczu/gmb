<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Workspace;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

use function Laravel\Ai\agent;

/**
 * Rewrites the owner's rough report-builder notes into crisp guidance for the
 * report AI (staff roster with canonical names + aliases, emphasis hints).
 * A setup convenience, not a billable reply: usage is logged to the ledger for
 * cost auditing (delta 0) and never counts against the AI cap.
 */
class InstructionImprover
{
    public function __construct(private readonly AiCreditService $credits) {}

    public function improve(string $rough, ?Workspace $workspace = null): string
    {
        $rough = trim($rough);

        if ($rough === '') {
            throw new RuntimeException('Nothing to improve.');
        }

        if (config('services.ai.driver') === 'fake') {
            return $rough;
        }

        $model = (string) config('services.ai.model', 'claude-opus-4-8');

        $response = agent(instructions: $this->systemPrompt())
            ->prompt($rough, provider: Lab::Anthropic, model: $model);

        $text = trim((string) $response->text);

        if ($text === '') {
            throw new RuntimeException('The AI returned an empty result.');
        }

        if ($workspace instanceof Workspace) {
            $this->credits->logUsage(
                workspace: $workspace,
                reason: 'instruction_improve',
                model: $model,
                inputTokens: (int) ($response->usage->promptTokens ?? 0),
                outputTokens: (int) ($response->usage->completionTokens ?? 0),
            );
        }

        return mb_substr($text, 0, 2000);
    }

    private function systemPrompt(): string
    {
        return implode("\n", [
            'You polish the guidance a business owner writes for an AI that generates their review-performance report.',
            'The AI mainly uses this guidance to attribute staff mentions in reviews to the right person, so restructure the input into clear rules:',
            '- List each staff member once with their canonical (full) name, followed by any nicknames, short forms or misspellings to merge into it (e.g. "Suleyman (also written Suly, Suli)").',
            '- Keep any other emphasis or context hints the owner wrote, each as its own short line.',
            'Rules: keep the SAME language the owner wrote in. Do not invent names, facts or hints that are not in the input. No preamble, no markdown, no quotes around the output — return only the improved guidance text, under 2000 characters.',
            ...Humanizer::rules(),
        ]);
    }
}
