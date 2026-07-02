<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * A one-time "top up AI replies" pack. Buying it grants `credits` AI-reply
 * credits to the workspace balance; those credits are spent automatically once
 * the plan's monthly allowance is exhausted (1 credit = 1 AI reply). `eur` is
 * the display price; the actual charge comes from the Stripe one-time price.
 */
class CreditPack
{
    public function __construct(
        public readonly string $key,
        public readonly int $credits,
        public readonly ?string $priceId,  // Stripe one-time price id
        public readonly int $eur,          // display price (EUR)
    ) {}
}
