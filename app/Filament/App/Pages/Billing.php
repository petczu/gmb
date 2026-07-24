<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Billing\Credits;
use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Services\Billing\AiUsageService;
use App\Services\Billing\LocationBilling;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Stripe\Exception\InvalidRequestException;

class Billing extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'billing';

    protected string $view = 'filament.app.pages.billing';

    /** Billing interval toggle: 'month' | 'year'. */
    public string $interval = 'month';

    /** Past invoices, lazily fetched from Stripe when the Invoices tab opens. */
    public ?array $invoices = null;

    /** Quantity selected in the "Customize credits" slider/presets. */
    public int $creditQty = 50;

    /** Auto top-up settings (bound to the form in the Top up section). */
    public bool $autoRechargeEnabled = false;

    public int $autoRechargeThreshold = 5;

    public int $autoRechargeAmount = 50;

    public static function getNavigationLabel(): string
    {
        return __('nav.billing');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_billing') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_billing') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    protected function billing(): LocationBilling
    {
        return app(LocationBilling::class);
    }

    /** @return array<string, mixed> */
    public function viewData(): array
    {
        $billing = $this->billing();
        $workspace = $this->workspace();
        $subscription = $billing->subscription($workspace);

        $usage = app(AiUsageService::class);

        return [
            'enabled' => $billing->enabled(),
            'plans' => Plans::all(),
            'hasYearly' => Plans::hasYearly(),
            'interval' => $this->interval,
            'currentPlan' => $billing->plan($workspace)?->key,
            'subscribed' => $billing->subscribed($workspace),
            'onTrial' => $billing->onTrial($workspace),
            'onGracePeriod' => (bool) $subscription?->onGracePeriod(),
            'locationCount' => $billing->locationCount(),
            'aiUsed' => $usage->autoRepliesThisMonth(),
            'aiCap' => $billing->aiReplyCap($workspace),
            'reportsUsed' => $usage->reportsThisMonth($workspace),
            'reportCap' => $billing->reportCap($workspace),
            'trialDaysLeft' => ($end = $billing->trialEndsAt($workspace)) ? max(0, (int) ceil(now()->floatDiffInDays($end))) : null,
            'hasUsedTrial' => $billing->hasUsedTrial($workspace),
            'nextPayment' => $billing->nextPayment($workspace),
            'creditBalance' => app(AiCreditService::class)->balance($workspace),
            'creditsSpentThisMonth' => app(AiCreditService::class)->spentThisMonth($workspace),
            'creditsAvailable' => $billing->enabled() && Credits::available(),
            'creditPrice' => Credits::pricePerCredit(),
            'creditMin' => Credits::min(),
            'creditMax' => Credits::max(),
            'creditSliderMax' => Credits::sliderMax(),
            'creditPresets' => Credits::presets(),
            'creditVolumeThreshold' => Credits::hasVolumeDiscount() ? Credits::volumeThreshold() : 0,
            'creditVolumeDiscount' => Credits::volumeDiscountPercent(),
            'reportCredits' => $usage->reportCredits(),
            'canceled' => (bool) $subscription?->canceled(),
            'cancelAt' => $subscription?->ends_at,
            'hasInvoices' => $billing->enabled() && $workspace->stripe_id !== null,
            'hasCard' => (bool) $workspace->hasDefaultPaymentMethod(),
            'billingProfile' => $this->billingProfileSummary($workspace),
            // Complete = Stripe Checkout can skip the address + VAT forms.
            'billingProfileComplete' => filled($workspace->legal_name)
                && filled($workspace->billing_country)
                && filled($workspace->address_line1)
                && filled($workspace->postal_code)
                && filled($workspace->city)
                && ($workspace->entity_type === 'individual' || filled($workspace->vat_number)),
        ];
    }

    /** One-line company summary for "invoices go to …" (shared with Checkout). */
    protected function billingProfileSummary(Workspace $workspace): ?string
    {
        return $this->billing()->billingSummaryLine($workspace);
    }

    /** The workspace's Cashier subscription, if any. */
    protected function subscriptionModel(): ?Subscription
    {
        return $this->billing()->subscription($this->workspace());
    }

    protected function subscriptionCancelable(): bool
    {
        $sub = $this->subscriptionModel();

        // Hide Cancel for an already-canceled sub (Cashier canceled() only checks
        // ends_at, which can be stale, so also check the real Stripe status).
        return $this->billing()->enabled()
            && $sub !== null
            && $sub->valid()
            && ! $sub->canceled()
            && $sub->stripe_status !== 'canceled';
    }

    protected function subscriptionResumable(): bool
    {
        $sub = $this->subscriptionModel();

        return $sub !== null && $sub->onGracePeriod() && $sub->canceled();
    }

    /** Lazily load past invoices from Stripe the first time the Invoices tab opens. */
    public function loadInvoices(): void
    {
        if ($this->invoices !== null) {
            return;
        }

        $workspace = $this->workspace();

        if (! $this->billing()->enabled() || $workspace->stripe_id === null) {
            $this->invoices = [];

            return;
        }

        try {
            $this->invoices = $workspace->invoices()
                ->map(fn ($invoice): array => [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->translatedFormat('j. M Y'),
                    'total' => $invoice->total(),
                    'url' => route('billing.invoice', $invoice->id),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Loading invoices failed', ['error' => $e->getMessage()]);
            $this->invoices = [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage')
                ->label(__('pages/billing.manage_billing'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->visible(fn (): bool => $this->billing()->enabled() && $this->workspace()->stripe_id !== null)
                ->action(fn () => $this->redirect($this->workspace()->billingPortalUrl(url('/billing')))),

            Action::make('resume')
                ->label(__('pages/billing.resume'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->visible(fn (): bool => $this->subscriptionResumable())
                ->action(function (): void {
                    $this->subscriptionModel()?->resume();
                    Notification::make()->title(__('pages/billing.resumed'))->success()->send();
                }),

            Action::make('cancel')
                ->label(__('pages/billing.cancel'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->subscriptionCancelable())
                ->requiresConfirmation()
                ->modalHeading(__('pages/billing.cancel_heading'))
                ->modalDescription(__('pages/billing.cancel_desc'))
                ->modalSubmitActionLabel(__('pages/billing.cancel_confirm'))
                ->action(function (): void {
                    $subscription = $this->subscriptionModel();
                    if ($subscription === null) {
                        return;
                    }

                    try {
                        // Cancel at period end: keeps access (and any remaining trial) until paid-through.
                        if (! $subscription->canceled()) {
                            $subscription->cancel();
                        }
                    } catch (InvalidRequestException $e) {
                        // Already canceled on Stripe but our local record was stale —
                        // pull the real status so the UI matches, then report success.
                        $subscription->syncStripeStatus();
                    }

                    Notification::make()->title(__('pages/billing.canceled'))->body(__('pages/billing.canceled_body'))->success()->send();
                }),
        ];
    }

    /**
     * Entry point from the plan buttons. If the company billing details are
     * missing, offer to fill them first (optional); otherwise go straight to
     * Stripe Checkout.
     */
    public function startCheckout(string $planKey): mixed
    {
        if ($this->billing()->billingComplete($this->workspace())) {
            return $this->goToCheckout($planKey);
        }

        $this->mountAction('subscribe', ['plan' => $planKey]);

        // `mixed` does not include void — the modal path must return explicitly.
        return null;
    }

    /** Modal asking whether to add billing details before checkout. */
    public function subscribeAction(): Action
    {
        return Action::make('subscribe')
            ->label(__('pages/billing.subscribe'))
            ->modalIcon(Heroicon::OutlinedBuildingOffice2)
            ->modalHeading(__('pages/billing.subscribe_heading'))
            ->modalDescription(__('pages/billing.subscribe_desc'))
            ->modalSubmitActionLabel(__('pages/billing.subscribe_submit'))
            ->extraModalFooterActions([
                Action::make('addDetails')
                    ->label(__('pages/billing.add_billing_details'))
                    ->color('gray')
                    ->url(Company::getUrl()),
            ])
            ->action(fn (array $arguments) => $this->goToCheckout($arguments['plan']));
    }

    /** Redirect to Stripe Checkout for the given plan (current interval). */
    protected function goToCheckout(string $planKey): mixed
    {
        $checkout = $this->billing()->checkout(
            $this->workspace(),
            $planKey,
            $this->interval,
            url('/billing?checkout=success'),
            url('/billing'),
        );

        return redirect()->away($checkout->url);
    }

    /** Open the Stripe billing portal so the user can add/set a default card. */
    public function addPaymentMethod(): mixed
    {
        $workspace = $this->workspace();
        $workspace->createOrGetStripeCustomer();

        return redirect()->away($workspace->billingPortalUrl(url('/billing')));
    }

    /** Redirect to Stripe Checkout to buy a custom number of credits. */
    public function buyCredits(int $quantity): mixed
    {
        $checkout = $this->billing()->buyCredits(
            $this->workspace(),
            $quantity,
            url('/billing?pack=success'),
            url('/billing'),
        );

        return redirect()->away($checkout->url);
    }

    /** Switch an existing subscription to another plan / interval. */
    public function switchPlan(string $planKey): void
    {
        $this->billing()->swap($this->workspace(), $planKey, $this->interval);

        Notification::make()->title(__('pages/billing.plan_updated'))->success()->send();
    }

    /** Undo a pending cancellation while still inside the grace period. */
    public function resumeSubscription(): void
    {
        $subscription = $this->subscriptionModel();

        if ($subscription !== null && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        Notification::make()->title(__('pages/billing.resumed'))->success()->send();
    }

    /** Persist the auto top-up settings (toggle + threshold + credit amount). */
    public function saveAutoRecharge(): void
    {
        $workspace = $this->workspace();

        $workspace->setAttribute('auto_recharge_enabled', $this->autoRechargeEnabled);
        $workspace->setAttribute('auto_recharge_threshold', max(0, $this->autoRechargeThreshold));
        $workspace->setAttribute('auto_recharge_amount', Credits::clamp($this->autoRechargeAmount));
        $workspace->save();

        Notification::make()->title(__('pages/billing.auto_recharge_saved'))->success()->send();
    }

    public function mount(): void
    {
        $workspace = $this->workspace();
        $this->autoRechargeEnabled = $workspace->autoRechargeEnabled();
        $this->autoRechargeThreshold = $workspace->autoRechargeThreshold();
        $this->autoRechargeAmount = $workspace->autoRechargeAmount();
        $this->creditQty = Credits::clamp($workspace->autoRechargeAmount());

        if (request()->query('checkout') === 'success') {
            // Record the new subscription right away (don't wait for the webhook).
            $this->billing()->syncFromStripe($this->workspace());

            Notification::make()->title(__('pages/billing.subscription_active'))->body(__('pages/billing.subscription_active_body'))->success()->send();
        }

        if (request()->query('pack') === 'success') {
            // Credits are granted by the checkout webhook; it may arrive a moment
            // after the redirect, so the balance can lag by a few seconds.
            Notification::make()
                ->title(__('pages/billing.topup_purchased'))
                ->body(__('pages/billing.topup_purchased_body'))
                ->success()
                ->send();
        }
    }
}
