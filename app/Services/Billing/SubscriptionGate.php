<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Workspace;
use Carbon\CarbonInterface;

/**
 * Decides whether a workspace may use the app based on its subscription.
 *
 * States:
 *  - off            billing not configured (local/dev) → never blocks
 *  - ok             active / on cancellation grace → full access
 *  - trial          on the free trial → full access
 *  - grace          payment failed but within the dunning grace window
 *                   (service keeps working for BILLING_GRACE_DAYS days)
 *  - needs_plan     no usable subscription → must choose a plan (blocks)
 *  - payment_problem  payment failed and grace expired (blocks)
 */
class SubscriptionGate
{
    public function __construct(private readonly LocationBilling $billing) {}

    public function state(Workspace $workspace): string
    {
        if (! $this->billing->enabled()) {
            return 'off';
        }

        $subscription = $this->billing->subscription($workspace);

        if ($subscription !== null) {
            if ($subscription->onTrial()) {
                return 'trial';
            }

            if ($subscription->active() || $subscription->onGracePeriod()) {
                return 'ok';
            }

            if ($subscription->pastDue()) {
                return now()->lessThan($this->graceEndsAt($subscription)) ? 'grace' : 'payment_problem';
            }
        }

        // Card-less local trial (no Stripe objects yet).
        if ($workspace->onGenericTrial()) {
            return 'trial';
        }

        // No subscription / canceled / incomplete / expired trial → must subscribe.
        return 'needs_plan';
    }

    /** Blocking states require an interstitial before using the app. */
    public function blocks(Workspace $workspace): bool
    {
        return in_array($this->state($workspace), ['needs_plan', 'payment_problem'], true);
    }

    /** Grace end date for the workspace's past-due subscription, or null. */
    public function graceEndsAtFor(Workspace $workspace): ?CarbonInterface
    {
        $subscription = $this->billing->subscription($workspace);

        return $subscription && $subscription->pastDue() ? $this->graceEndsAt($subscription) : null;
    }

    /** When the dunning grace window ends for a past-due subscription. */
    public function graceEndsAt($subscription): CarbonInterface
    {
        $days = (int) config('services.billing.grace_days', 7);

        // updated_at ≈ when the status last changed to past_due (set by the
        // Cashier webhook). Service stays usable until this moment.
        return ($subscription->updated_at ?? now())->copy()->addDays($days);
    }
}
