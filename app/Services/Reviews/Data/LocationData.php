<?php

declare(strict_types=1);

namespace App\Services\Reviews\Data;

/**
 * Provider-agnostic location DTO. Whatever provider (Fake/Zernio/…) returns is
 * normalized into this shape before it touches the app/DB.
 */
final readonly class LocationData
{
    public function __construct(
        public string $externalId,
        public string $name,
        public ?string $address = null,
        public ?string $placeId = null,
        public ?string $phone = null,
        public ?string $websiteUrl = null,
        public ?float $rating = null,
        public int $reviewsCount = 0,
        public bool $isVerified = true,
        public string $status = 'active',
    ) {}
}
