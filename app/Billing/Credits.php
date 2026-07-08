<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * Credit purchasing model. Credits are a single top-up currency spent once a
 * plan's monthly allowance is exhausted: 1 AI reply = 1 credit, 1 AI report =
 * `services.ai.report_credits` credits. Purchased credits never expire.
 *
 * A custom amount is bought as a single Stripe per-credit price
 * (config services.billing.credit_price_id, e.g. €0.08/credit) with the chosen
 * quantity, so any amount between min() and max() can be purchased.
 */
class Credits
{
    /** Base price charged per credit, in the Cashier currency (default €0.08). */
    public static function pricePerCredit(): float
    {
        return (float) config('services.billing.credit_price', 0.08);
    }

    /** Effective per-credit price for a quantity (applies the volume discount). */
    public static function pricePerCreditFor(int $quantity): float
    {
        $base = self::pricePerCredit();

        if (self::qualifiesForVolume($quantity)) {
            return round($base * (1 - self::volumeDiscountPercent() / 100), 4);
        }

        return $base;
    }

    /** Base Stripe one-time per-credit price id, or null when not configured. */
    public static function priceId(): ?string
    {
        return ((string) config('services.billing.credit_price_id', '')) ?: null;
    }

    /** Discounted (volume) per-credit price id, or null when not configured. */
    public static function volumePriceId(): ?string
    {
        return ((string) config('services.billing.credit_price_id_volume', '')) ?: null;
    }

    /** The price id to charge for a given quantity (volume price at/above the threshold). */
    public static function priceIdFor(int $quantity): ?string
    {
        return self::qualifiesForVolume($quantity) ? self::volumePriceId() : self::priceId();
    }

    public static function hasVolumeDiscount(): bool
    {
        return self::volumePriceId() !== null && self::volumeDiscountPercent() > 0;
    }

    public static function volumeThreshold(): int
    {
        return (int) config('services.billing.credit_volume_threshold', 500);
    }

    public static function volumeDiscountPercent(): int
    {
        // Clamped: a mistyped env value must never render "save 104%".
        return min(95, max(0, (int) config('services.billing.credit_volume_discount', 10)));
    }

    /** True when the quantity reaches the volume threshold AND a discount price exists. */
    public static function qualifiesForVolume(int $quantity): bool
    {
        return self::hasVolumeDiscount() && $quantity >= self::volumeThreshold();
    }

    public static function available(): bool
    {
        return self::priceId() !== null;
    }

    public static function min(): int
    {
        return (int) config('services.billing.credit_min', 10);
    }

    /** Hard cap for the custom-amount input. */
    public static function max(): int
    {
        return (int) config('services.billing.credit_max', 5000);
    }

    /** Slider quick-range max (the custom input can go higher, up to max()). */
    public static function sliderMax(): int
    {
        return (int) config('services.billing.credit_slider_max', 500);
    }

    /**
     * Quick-buy presets shown alongside the slider.
     *
     * @return array<int, int>
     */
    public static function presets(): array
    {
        return [50, 100, 500, 1000];
    }

    /** Clamp a requested quantity into the allowed range. */
    public static function clamp(int $quantity): int
    {
        return max(self::min(), min(self::max(), $quantity));
    }

    /** Total price for a quantity of credits (with volume discount), rounded to cents. */
    public static function cost(int $quantity): float
    {
        $quantity = self::clamp($quantity);

        return round($quantity * self::pricePerCreditFor($quantity), 2);
    }
}
