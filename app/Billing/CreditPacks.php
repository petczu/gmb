<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * The AI-reply top-up pack catalogue. Each pack is a Stripe one-time price
 * (config services.billing.credit_packs.*) that grants N AI-reply credits.
 * Credits are spent automatically once the plan's monthly allowance is used up.
 */
class CreditPacks
{
    /**
     * @return array<string, CreditPack>
     */
    public static function all(): array
    {
        $packs = (array) config('services.billing.credit_packs', []);

        $result = [];
        foreach ($packs as $key => $pack) {
            // Empty env vars arrive as '' — coerce to null so unset packs disable.
            $priceId = ($pack['price'] ?? null) ?: null;

            $result[$key] = new CreditPack(
                (string) $key,
                (int) ($pack['credits'] ?? 0),
                $priceId,
                (int) ($pack['eur'] ?? 0),
            );
        }

        return $result;
    }

    public static function find(?string $key): ?CreditPack
    {
        return $key ? (self::all()[$key] ?? null) : null;
    }

    public static function fromPriceId(?string $priceId): ?CreditPack
    {
        if (! $priceId) {
            return null;
        }

        foreach (self::all() as $pack) {
            if ($pack->priceId === $priceId) {
                return $pack;
            }
        }

        return null;
    }

    /** Any pack price id configured? (controls whether the top-up UI shows.) */
    public static function available(): bool
    {
        foreach (self::all() as $pack) {
            if ($pack->priceId !== null) {
                return true;
            }
        }

        return false;
    }
}
