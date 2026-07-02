<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Billing\Credits;
use App\Mail\AutoRechargeFailedMail;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\CardException;
use Throwable;

/**
 * Automatic AI-credit top-up ("auto-recharge"). When a workspace opts in and its
 * central credit balance drops to/below its chosen threshold, this charges the
 * saved card off-session for the configured pack and grants the credits
 * synchronously.
 *
 * The Cashier off-session charge (invoicePrice) fires invoice.payment_succeeded,
 * NOT checkout.session.completed, so the GrantCreditPack webhook listener does
 * NOT grant these credits — this service credits the ledger itself (idempotent on
 * the invoice id). All work is on the central connection; NO tenancy init needed.
 */
class AutoRechargeService
{
    /**
     * Cooldown window between auto top-up attempts. Stamped BEFORE the charge, so a
     * crash mid-charge can't loop into a runaway double-charge; it also throttles a
     * failing card to one attempt per hour rather than every scheduler tick.
     */
    private const COOLDOWN_HOURS = 1;

    public function __construct(
        private readonly LocationBilling $billing,
        private readonly AiCreditService $credits,
    ) {}

    /**
     * True when every gate passes: billing live, opted in, packs configured, the
     * chosen pack is priced, a saved card exists, the balance is at/below the
     * threshold, and we're not inside the cooldown window.
     */
    public function eligible(Workspace $workspace): bool
    {
        if (! $this->billing->enabled()) {
            return false;
        }

        if (! $workspace->autoRechargeEnabled()) {
            return false;
        }

        if (! Credits::available()) {
            return false;
        }

        if ($workspace->autoRechargeAmount() < Credits::min()) {
            return false;
        }

        if (! $workspace->hasDefaultPaymentMethod()) {
            return false;
        }

        if ($this->credits->balance($workspace) > $workspace->autoRechargeThreshold()) {
            return false;
        }

        return ! $this->withinCooldown($workspace);
    }

    /**
     * Attempt an auto top-up. Self-guards via eligible(). Stamps the cooldown and
     * saves it BEFORE charging; on a successful off-session charge, grants the pack
     * (idempotent on the invoice id). On SCA/card/other failure, logs a warning and
     * emails the owner to update their card — the setting is left ON so they can fix
     * the card (the cooldown prevents repeated charge attempts).
     */
    public function recharge(Workspace $workspace): bool
    {
        if (! $this->eligible($workspace)) {
            return false;
        }

        $quantity = Credits::clamp($workspace->autoRechargeAmount());
        $priceId = Credits::priceIdFor($quantity);
        // eligible() guarantees a configured price, but re-assert for static safety.
        if ($priceId === null) {
            return false;
        }

        // Stamp + persist the cooldown BEFORE the charge so a crash can't loop.
        $workspace->setAttribute('auto_recharge_last_at', now()->toDateTimeString());
        $workspace->save();

        try {
            $invoice = $workspace->invoicePrice($priceId, $quantity);

            $this->credits->creditPack($workspace, $quantity, (string) $invoice->id);

            Log::info('AutoRecharge: topped up', [
                'workspace' => $workspace->id,
                'credits' => $quantity,
                'invoice' => $invoice->id,
            ]);

            return true;
        } catch (IncompletePayment $e) {
            $this->notifyFailure($workspace, 'sca_required', $e);

            return false;
        } catch (CardException $e) {
            $this->notifyFailure($workspace, 'card_declined', $e);

            return false;
        } catch (Throwable $e) {
            $this->notifyFailure($workspace, 'error', $e);

            return false;
        }
    }

    /** Inside the cooldown window since the last attempt? */
    private function withinCooldown(Workspace $workspace): bool
    {
        $lastAt = $this->lastAttemptAt($workspace);

        return $lastAt !== null && $lastAt->isAfter(now()->subHours(self::COOLDOWN_HOURS));
    }

    private function lastAttemptAt(Workspace $workspace): ?CarbonInterface
    {
        $value = $workspace->getAttribute('auto_recharge_last_at');

        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function notifyFailure(Workspace $workspace, string $reason, Throwable $e): void
    {
        Log::warning('AutoRecharge: charge failed', [
            'workspace' => $workspace->id,
            'reason' => $reason,
            'error' => $e->getMessage(),
        ]);

        $billingUrl = rtrim((string) config('app.url'), '/').'/billing';

        app(\App\Services\Notifications\NotificationDispatcher::class)->dispatch(
            $workspace,
            \App\Services\Notifications\NotificationCategory::BILLING,
            fn (string $name, string $lang) => new AutoRechargeFailedMail(
                name: $name,
                billingUrl: $billingUrl,
                lang: $lang,
            ),
        );
    }
}
