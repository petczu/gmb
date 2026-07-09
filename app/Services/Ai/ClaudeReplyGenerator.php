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
            // Shared humanizer rules: replies must read like a real person, not AI.
            ...Humanizer::rules(),
            '- Be specific to what the review actually says.',
            // Review-reply craft: universal Google-review conventions that hold
            // regardless of the configured persona (which may refine them).
            'Google review conventions:',
            '- Reviewer names are often usernames: use only a cleaned first name ("John D." is John, drop stray letters or digits), skip the name entirely when it is messy, and do not open every reply with the name or the same greeting.',
            '- A rating with no text gets 1-2 short sentences: thank them and invite them back.',
            '- For a critical review: acknowledge honestly, apologize naturally, offer to make it right directly. Never defensive, no emojis.',
            '- For a positive review: at most one light emoji, and only when it fits the tone.',
            '- Vary structure and opening between replies; do not follow one template.',
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
