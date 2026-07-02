<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Data\GeneratedReply;

/**
 * Generates a review reply. v1 implementations: FakeReplyGenerator (templated,
 * dev/test) and ClaudeReplyGenerator (Anthropic). Selected via services.ai.driver.
 */
interface ReplyGenerator
{
    public function generate(
        string $reviewText,
        int $rating,
        ?string $authorName,
        string $businessName,
        ?string $tone,
        ?string $instruction,
        ?string $language,
    ): GeneratedReply;
}
