<?php

declare(strict_types=1);

namespace App\Billing;

/**
 * The plan catalogue. Prices are per location/month; Stripe price ids come from
 * config (services.billing.prices.*). Feature flags gate paid capabilities;
 * `aiReplyCap` is the monthly AI auto-reply allowance.
 */
class Plans
{
    // Gateable capabilities (used by Plan::allows()).
    public const SCHEDULED_REPORTS = 'scheduled_reports';
    public const PERIOD_COMPARISON = 'period_comparison';
    public const FULL_AUTOMATIONS = 'full_automations';
    public const WHITE_LABEL = 'white_label';
    public const CUSTOM_ROLES = 'custom_roles';
    public const CLIENT_ACCESS = 'client_access';
    public const MCP = 'mcp';
    public const API = 'api';

    /**
     * @return array<string, Plan>
     */
    public static function all(): array
    {
        // Empty env vars arrive as '' — coerce to null so unset prices disable.
        $m = array_map(fn ($v) => $v ?: null, (array) config('services.billing.prices', []));
        $y = array_map(fn ($v) => $v ?: null, (array) config('services.billing.prices_yearly', []));

        return [
            'starter' => new Plan('starter', 'Starter', 12, $m['starter'] ?? null, $y['starter'] ?? null, 20, 4, []),

            'growth' => new Plan('growth', 'Growth', 24, $m['growth'] ?? null, $y['growth'] ?? null, 250, 20, [
                self::SCHEDULED_REPORTS,
                self::PERIOD_COMPARISON,
                self::FULL_AUTOMATIONS,
            ]),

            'pro' => new Plan('pro', 'Pro', 49, $m['pro'] ?? null, $y['pro'] ?? null, 1500, 50, [
                self::SCHEDULED_REPORTS,
                self::PERIOD_COMPARISON,
                self::FULL_AUTOMATIONS,
                self::WHITE_LABEL,
                self::CUSTOM_ROLES,
                self::CLIENT_ACCESS,
                self::MCP,
                self::API,
            ]),
        ];
    }

    public static function find(?string $key): ?Plan
    {
        return $key ? (self::all()[$key] ?? null) : null;
    }

    /** Any yearly prices configured? (controls the Monthly/Yearly toggle.) */
    public static function hasYearly(): bool
    {
        foreach (self::all() as $plan) {
            if ($plan->yearlyPriceId !== null) {
                return true;
            }
        }

        return false;
    }

    public static function fromPriceId(?string $priceId): ?Plan
    {
        if (! $priceId) {
            return null;
        }

        foreach (self::all() as $plan) {
            if (in_array($priceId, [$plan->priceId, $plan->yearlyPriceId], true)) {
                return $plan;
            }
        }

        return null;
    }
}
