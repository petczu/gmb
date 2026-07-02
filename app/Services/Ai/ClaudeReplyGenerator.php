<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Data\GeneratedReply;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

use function Laravel\Ai\agent;

/**
 * Anthropic Claude review-reply generator built on the official Laravel AI SDK
 * (laravel/ai). The dynamic per-review persona/tone/language is passed as the
 * agent's instructions (system prompt); the review is the prompt.
 *
 * Model defaults to claude-opus-4-8 (config services.ai.model — switch to
 * claude-sonnet-4-6 / claude-haiku-4-5 for cheaper, high-volume replies).
 * The ANTHROPIC_API_KEY is read by config/ai.php.
 */
class ClaudeReplyGenerator implements ReplyGenerator
{
    public function generate(
        string $reviewText,
        int $rating,
        ?string $authorName,
        string $businessName,
        ?string $tone,
        ?string $instruction,
        ?string $language,
    ): GeneratedReply {
        $model = (string) config('services.ai.model', 'claude-opus-4-8');

        $system = $this->buildSystemPrompt($businessName, $tone, $instruction, $language);
        $userMessage = $this->buildUserMessage($reviewText, $rating, $authorName);

        $response = agent(instructions: $system)
            ->prompt($userMessage, provider: Lab::Anthropic, model: $model);

        $text = trim((string) $response->text);
        if ($text === '') {
            throw new RuntimeException('Claude returned an empty reply.');
        }

        return new GeneratedReply(
            text: $text,
            model: $model,
            inputTokens: (int) ($response->usage->promptTokens ?? 0),
            outputTokens: (int) ($response->usage->completionTokens ?? 0),
        );
    }

    private function buildSystemPrompt(string $businessName, ?string $tone, ?string $instruction, ?string $language): string
    {
        $lines = [
            "You are the owner of \"{$businessName}\" replying to a Google review.",
            'Write a concise, warm, professional reply (2-4 sentences).',
            'Address the reviewer naturally. Do not invent facts not present in the review.',
            'Output ONLY the reply text. No preamble, quotes, labels, or sign-off placeholders.',
            // Humanizer rules: make replies read like a real person, not AI.
            'Write like a real human, not a chatbot. Follow these rules strictly:',
            '- No em dashes or en dashes. Use a comma, period, or parentheses instead.',
            '- Avoid AI cliches and promotional words: delighted, thrilled, valued, vibrant, elevate, seamless, truly, testament, journey, we strive.',
            '- No sycophantic or servile filler.',
            '- Vary sentence length. Be specific to what the review actually says. Plain, genuine, human.',
            '- Do not force three-item lists or repeat the same idea with synonyms.',
        ];

        if ($language) {
            $lines[] = "Write the reply in this language: {$language}.";
        } else {
            $lines[] = 'Write the reply in the same language as the review.';
        }
        if ($tone) {
            $lines[] = 'Tone/template guidance: '.trim($tone);
        }
        if ($instruction) {
            $lines[] = 'Additional instruction: '.trim($instruction);
        }

        return implode("\n", $lines);
    }

    private function buildUserMessage(string $reviewText, int $rating, ?string $authorName): string
    {
        $author = $authorName ?: 'Anonymous';

        return "Reviewer: {$author}\nRating: {$rating} out of 5 stars\nReview:\n{$reviewText}";
    }
}
