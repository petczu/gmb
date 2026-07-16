<?php

declare(strict_types=1);

namespace App\Services\Reviews\Data;

use Carbon\CarbonInterface;

/**
 * Provider-agnostic review DTO.
 */
final readonly class ReviewData
{
    public function __construct(
        public string $externalId,
        public string $locationExternalId,
        public int $rating,
        public ?string $authorName = null,
        public ?string $text = null,
        public ?string $reviewLink = null,
        public ?CarbonInterface $createdAtExternal = null,
        public ?string $replyText = null,
        public ?CarbonInterface $repliedAt = null,
        public int $photoCount = 0,
        /** @var list<string> reviewer-uploaded photo urls (Google Business only) */
        public array $photos = [],
    ) {}
}
