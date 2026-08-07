<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Writes a Google Business post caption in the company's own voice. The
 * prompt carries recent published captions as style examples, so the draft
 * sounds like the business — same language, tone, emoji habits and length.
 */
#[Provider(Lab::Anthropic)]
#[MaxTokens(2048)]
#[Temperature(0.7)]
#[Timeout(90)]
class PostWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return implode("\n", [
            'You write Google Business Profile posts for local businesses.',
            'You are given the business context, an occasion, and recent posts as STYLE EXAMPLES.',
            'Match the examples closely: same language, tone, emoji usage, formatting habits and typical length. When no examples exist, write warm, concise and professional copy in the given language.',
            'Rules:',
            '- One ready-to-publish caption only. No hashtags unless the examples use them. No placeholders like [Name]: write finished copy.',
            '- 400-1200 characters. Mention the occasion naturally; end with a soft call to action.',
            '- Never invent discounts, prices, opening hours or events the input does not mention.',
            'Write like a human, not like an AI:',
            '- NEVER use em dashes (—) or en dashes (–). Use commas, colons or separate sentences instead.',
            '- Avoid AI tells: the rule of three ("connect, laugh, and create memories"), negative parallelisms ("not just X, it\'s Y"), rhetorical questions as openers ("why not..?"), and words like elevate, unforgettable, seamless, vibrant, nestled, delve.',
            '- No promotional fluff or superlatives the examples would not use. Concrete beats grand: say what happens, not how amazing it is.',
            '- At most one exclamation mark and at most two emojis in the whole caption, and only if the examples use them.',
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'caption' => $schema->string()->required(),
        ];
    }
}
