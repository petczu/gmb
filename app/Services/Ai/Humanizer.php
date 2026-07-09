<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Canonical "write like a human" rules shared by every AI text generator
 * (review replies, agent persona generation, instruction polishing, report
 * narrative). Keeps AI artifacts (em dashes, cliche vocabulary, rule-of-three
 * padding) out of any text a customer might read.
 */
final class Humanizer
{
    /** @return list<string> prompt lines, ready to append to a system prompt */
    public static function rules(): array
    {
        return [
            'Write like a real human, not a chatbot. Follow these rules strictly:',
            '- No em dashes or en dashes anywhere in the text. Use a comma, period, colon or parentheses instead.',
            '- Avoid AI cliches and promotional words: delighted, thrilled, valued, vibrant, elevate, seamless, truly, testament, journey, we strive, delve, leverage, robust, pivotal, landscape, underscore.',
            '- No sycophantic or servile filler.',
            '- Vary sentence length. Plain, genuine, specific wording.',
            '- Do not force three-item lists or repeat the same idea with synonyms.',
        ];
    }
}
