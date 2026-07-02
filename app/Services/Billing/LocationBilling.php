<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Billing\CreditPacks;
use App\Billing\Credits;
use App\Billing\Plan;
use App\Billing\Plans;
use App\Models\Location;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Throwable;

/**
 * Per-location, tiered billing. The workspace runs ONE Stripe subscription
 * named 'location' whose quantity = connected locations and whose active price
 * determines the plan (Starter/Growth/Pro). The plan drives feature gates and
 * the monthly AI auto-reply allowance.
 *
 * Safe no-ops until Stripe is configured, so connect/automation keep working
 * in local/dev without billing.
 */
class LocationBilling
{
    public const SUBSCRIPTION = 'location';

    /** Stripe configured (secret key + at least the Starter price)? */
    public function enabled(): bool
    {
        return ! empty(config('cashier.secret'))
            && ! empty(config('services.billing.prices.starter'));
    }

    public function subscription(Workspace $workspace): ?Subscription
    {
        return $this->enabled() ? $workspace->subscription(self::SUBSCRIPTION) : null;
    }

    public function subscribed(Workspace $workspace): bool
    {
        return $this->enabled() && $workspace->subscribed(self::SUBSCRIPTION);
    }

    /** The active plan (from a real/trialing Stripe subscription), else null. */
    public function plan(Workspace $workspace): ?Plan
    {
        $subscription = $this->subscription($workspace);

        if ($subscription !== null && $subscription->valid()) {
            return Plans::fromPriceId($subscription->stripe_price);
        }

        return null;
    }

    /** On a Stripe trial (the subscription is in its trial period). */
    public function onTrial(Workspace $workspace): bool
    {
        return (bool) $this->subscription($workspace)?->onTrial();
    }

    public function trialEndsAt(Workspace $workspace): ?\Carbon\CarbonInterface
    {
        return $this->onTrial($workspace) ? $this->subscription($workspace)?->trial_ends_at : null;
    }

    /** Has this workspace ever had a Stripe subscription (so no new trial)? */
    public function hasUsedTrial(Workspace $workspace): bool
    {
        return $workspace->subscriptions()->exists();
    }

    /** Minimum company billing details required before starting a plan/trial. */
    public function billingComplete(Workspace $workspace): bool
    {
        return filled($workspace->billing_country) && filled($workspace->legal_name);
    }

    /**
     * The next charge: amount (plan price × locations) and date (trial end while
     * trialing, else the current period end). Null when there's no upcoming
     * charge (no plan, or cancelling at period end).
     *
     * @return array{amount: int, currency: string, date: ?\Carbon\CarbonInterface}|null
     */
    public function nextPayment(Workspace $workspace): ?array
    {
        $subscription = $this->subscription($workspace);
        $plan = $this->plan($workspace);

        if ($subscription === null || $plan === null || ! $subscription->valid() || $subscription->onGracePeriod()) {
            return null;
        }

        $yearly = $subscription->stripe_price === $plan->yearlyPriceId;
        $unit = $yearly ? $plan->yearlyPriceUsd() : $plan->priceUsd;

        $date = $subscription->trial_ends_at;
        if (! $subscription->onTrial()) {
            try {
                $date = \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end);
            } catch (Throwable $e) {
                $date = null;
            }
        }

        return [
            'amount' => $unit * max(1, $this->locationCount()),
            'currency' => strtoupper((string) config('cashier.currency', 'eur')),
            'date' => $date,
        ];
    }

    /**
     * Pull the workspace's Stripe subscription(s) into the local DB immediately
     * — used right after Checkout so the page reflects the new plan without
     * waiting for the (async) webhook. Reuses Cashier's webhook handler.
     */
    public function syncFromStripe(Workspace $workspace): void
    {
        if (! $this->enabled() || ! $workspace->hasStripeId()) {
            return;
        }

        try {
            $subscriptions = Cashier::stripe()->subscriptions->all([
                'customer' => $workspace->stripe_id,
                'status' => 'all',
                'limit' => 5,
            ]);

            foreach ($subscriptions->data as $stripeSubscription) {
                if (! in_array($stripeSubscription->status, ['active', 'trialing', 'past_due'], true)) {
                    continue;
                }

                $workspace->subscriptions()->firstOrCreate(
                    ['stripe_id' => $stripeSubscription->id],
                    [
                        'type' => self::SUBSCRIPTION,
                        'stripe_status' => $stripeSubscription->status,
                        'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? null,
                        'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                        'trial_ends_at' => $stripeSubscription->trial_end
                            ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end)
                            : null,
                        'ends_at' => null,
                    ]
                );
            }
        } catch (Throwable $e) {
            Log::warning('LocationBilling: syncFromStripe failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Put the company VAT number on the Stripe customer (EU reverse-charge). */
    private function syncVatTaxId(Workspace $workspace): void
    {
        if (! filled($workspace->vat_number) || ! filled($workspace->legal_name)) {
            return;
        }

        try {
            $already = $workspace->taxIds()->contains(fn ($t): bool => $t->value === $workspace->vat_number);
            if (! $already) {
                $workspace->createTaxId('eu_vat', $workspace->vat_number);
            }
        } catch (Throwable $e) {
            Log::warning('LocationBilling: VAT tax id sync failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function allows(Workspace $workspace, string $feature): bool
    {
        // Billing off (local/dev/self-host) → nothing is gated.
        if (! $this->enabled()) {
            return true;
        }

        return (bool) $this->plan($workspace)?->allows($feature);
    }

    /** Monthly AI auto-reply allowance for the active plan (0 when none). */
    public function aiReplyCap(Workspace $workspace): int
    {
        return $this->plan($workspace)?->aiReplyCap ?? 0;
    }

    /** Monthly AI-report allowance for the active plan (0 when none). */
    public function reportCap(Workspace $workspace): int
    {
        return $this->plan($workspace)?->reportCap ?? 0;
    }

    /** Number of connected (tracked) locations — assumes tenant context. */
    public function locationCount(): int
    {
        return Location::query()->count();
    }

    /**
     * Start a subscription to a plan via Stripe Checkout (quantity = locations,
     * with a trial). Returns the Checkout object (its ->url to redirect to).
     */
    public function checkout(Workspace $workspace, string $planKey, string $interval, string $successUrl, string $cancelUrl)
    {
        $plan = Plans::find($planKey);
        $priceId = $plan?->priceIdFor($interval);

        if ($priceId === null) {
            throw new \InvalidArgumentException("Unknown or unpriced plan: {$planKey} ({$interval})");
        }

        $this->prefillStripeCustomer($workspace);

        $builder = $workspace
            ->newSubscription(self::SUBSCRIPTION, $priceId)
            ->quantity(max(1, $this->locationCount()));

        // Pass trial as `trial_period_days` (integer) rather than letting Cashier
        // send a `trial_end` timestamp — the timestamp makes Stripe display
        // "13 days free" (it floors the partial day); the integer shows "14".
        // Only first-time subscribers get the trial (no double trials).
        $subscriptionData = [];
        if (! $workspace->subscriptions()->exists()) {
            $subscriptionData['trial_period_days'] = (int) config('services.billing.trial_days', 14);
        }

        // Stripe free-trial flow: while the amount due is €0 (during the trial)
        // Stripe does NOT require a card up front. https://docs.stripe.com/payments/checkout/free-trials
        $options = [
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_method_collection' => 'if_required',
            'subscription_data' => $subscriptionData ?: null,
            // Collect country (billing address) + VAT/Tax ID for invoices, and
            // save them onto the customer.
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true],
            'customer_update' => ['address' => 'auto', 'name' => 'auto'],
        ];

        // Optional Stripe Tax auto-calculation (needs Stripe Tax enabled +
        // origin address configured in the dashboard).
        if (config('services.billing.automatic_tax')) {
            $options['automatic_tax'] = ['enabled' => true];
        }

        return $builder->checkout(array_filter($options, fn ($v): bool => $v !== null));
    }

    /**
     * Buy a one-time "top up AI replies" pack via Stripe Checkout (mode=payment).
     * The credits are granted on the checkout.session.completed webhook (see
     * App\Listeners\GrantCreditPack). The pack key + workspace id are written to
     * BOTH the session and the payment_intent metadata so the webhook can resolve
     * them. Returns the Checkout object (its ->url to redirect to). Does NOT touch
     * the recurring per-location subscription.
     */
    /**
     * Checkout for a custom number of credits (per-credit price × quantity).
     * The granted amount is read back from metadata.credits by GrantCreditPack.
     */
    public function buyCredits(Workspace $workspace, int $quantity, string $successUrl, string $cancelUrl)
    {
        $quantity = Credits::clamp($quantity);
        $priceId = Credits::priceIdFor($quantity);

        if ($priceId === null) {
            throw new \InvalidArgumentException('Credit purchasing is not configured (no per-credit price).');
        }

        $this->prefillStripeCustomer($workspace);

        $metadata = [
            'credits' => (string) $quantity,
            'workspace_id' => $workspace->id,
        ];

        return $workspace->checkout([$priceId => $quantity], [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            // Save the card off-session so a single credit purchase also enables auto top-up.
            'payment_intent_data' => ['metadata' => $metadata, 'setup_future_usage' => 'off_session'],
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true],
            'customer_update' => ['address' => 'auto', 'name' => 'auto'],
        ]);
    }

    public function buyCreditPack(Workspace $workspace, string $packKey, string $successUrl, string $cancelUrl)
    {
        $pack = CreditPacks::find($packKey);
        $priceId = $pack?->priceId;

        if ($priceId === null) {
            throw new \InvalidArgumentException("Unknown or unpriced credit pack: {$packKey}");
        }

        $this->prefillStripeCustomer($workspace);

        $metadata = [
            'credit_pack' => $packKey,
            'workspace_id' => $workspace->id,
        ];

        return $workspace->checkout([$priceId => 1], [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true],
            'customer_update' => ['address' => 'auto', 'name' => 'auto'],
        ]);
    }

    /**
     * Pre-fill the Stripe customer from the company billing details so Checkout
     * shows name/address/VAT already filled in. Shared by plan checkout and the
     * one-time credit-pack checkout.
     */
    private function prefillStripeCustomer(Workspace $workspace): void
    {
        $workspace->createOrGetStripeCustomer();
        $workspace->updateStripeCustomer(array_filter([
            'email' => $workspace->stripeEmail(),
            'name' => $workspace->legal_name ?: $workspace->stripeName(),
            'address' => array_filter([
                'country' => $workspace->billing_country,
                'line1' => $workspace->address_line1,
                'line2' => $workspace->address_line2,
                'postal_code' => $workspace->postal_code,
                'city' => $workspace->city,
            ]) ?: null,
        ], fn ($v): bool => $v !== null));

        $this->syncVatTaxId($workspace);
    }

    /** Switch the active subscription to another plan/interval (keeps quantity). */
    public function swap(Workspace $workspace, string $planKey, string $interval): void
    {
        $priceId = Plans::find($planKey)?->priceIdFor($interval);
        $subscription = $this->subscription($workspace);

        if ($priceId === null || $subscription === null) {
            return;
        }

        // Switching plans from inside the grace period also undoes the pending
        // cancellation — Cashier's swap() keeps cancel_at_period_end otherwise.
        if ($subscription->onGracePeriod()) {
            $subscription->resume();
        }

        $subscription->swap($priceId);
    }

    /**
     * Align the subscription quantity with the number of connected locations.
     * No-op unless billing is set up and the workspace has a subscription.
     */
    public function syncQuantity(Workspace $workspace): void
    {
        if (! $this->subscribed($workspace)) {
            return;
        }

        $count = max(1, $this->locationCount());

        try {
            $workspace->subscription(self::SUBSCRIPTION)->updateQuantity($count);
        } catch (Throwable $e) {
            Log::warning('LocationBilling: updateQuantity failed', [
                'workspace' => $workspace->id,
                'count' => $count,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
