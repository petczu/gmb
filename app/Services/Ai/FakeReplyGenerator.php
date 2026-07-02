<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Data\GeneratedReply;

/**
 * Deterministic templated generator for dev/tests — no API key needed. Honors
 * rating band and tone/instruction so the auto-reply flow is fully exercisable.
 */
class FakeReplyGenerator implements ReplyGenerator
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
        $name = $authorName ? rtrim($authorName) : 'there';

        $body = match (true) {
            $rating >= 4 => "Thank you so much, {$name}! We're thrilled you had a great experience at {$businessName} and we hope to see you again soon.",
            $rating === 3 => "Thanks for the honest feedback, {$name}. We're glad you visited {$businessName} and we're always working to make your next visit even better.",
            default => "We're sorry to hear about your experience, {$name}. This isn't the standard we hold ourselves to at {$businessName} — please reach out so we can make it right.",
        };

        if ($tone) {
            $body .= ' '.trim($tone);
        }
        if ($instruction) {
            $body .= ' '.trim($instruction);
        }

        return new GeneratedReply(text: trim($body), model: 'fake');
    }
}
