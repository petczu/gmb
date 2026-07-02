<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * A subscription plan (Starter/Growth/Pro). Price is per LOCATION; the
 * subscription quantity = number of connected locations. Caps are monthly
 * allowances (enforced internally — never exposed as "credits"). `features`
 * gate paid capabilities. Each plan has a monthly and (optional) yearly price.
 */
class Plan
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly int $priceUsd,           // monthly, per location
        public readonly ?string $priceId,        // monthly Stripe price id
        public readonly ?string $yearlyPriceId,  // yearly Stripe price id (optional)
        public readonly int $aiReplyCap,         // AI auto-replies / month
        public readonly int $reportCap,          // AI reports / month
        /** @var array<int, string> */
        public readonly array $features,
    ) {}

    public function allows(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function priceIdFor(string $interval): ?string
    {
        return $interval === 'year' ? $this->yearlyPriceId : $this->priceId;
    }

    /** Yearly per-location price with the 20% annual discount baked in. */
    public function yearlyPriceUsd(): int
    {
        return (int) round($this->priceUsd * 12 * 0.8);
    }
}
