<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Billing\CreditPacks;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Grants a purchased AI-reply pack in response to the Stripe
 * checkout.session.completed webhook (delivered via Cashier). Only handles
 * one-time payment sessions (mode=payment, payment_status=paid); recurring
 * subscription checkouts are ignored. The grant is idempotent on the session
 * id, so Stripe's webhook retries never double-credit.
 *
 * Runs with NO tenant context — only central models (Workspace, the AI credit
 * ledger) are touched here.
 */
class GrantCreditPack
{
    public function __construct(private readonly AiCreditService $credits) {}

    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;
        if ($type !== 'checkout.session.completed') {
            return;
        }

        $session = $event->payload['data']['object'] ?? [];

        // Only one-time pack payments (not the recurring location subscription).
        if (($session['mode'] ?? null) !== 'payment' || ($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $metadata = $session['metadata'] ?? [];
        $workspaceId = $metadata['workspace_id'] ?? null;

        // New custom-amount purchases carry metadata.credits (quantity). Legacy
        // fixed packs carried metadata.credit_pack — still honoured.
        $creditsToGrant = isset($metadata['credits'])
            ? (int) $metadata['credits']
            : CreditPacks::find($metadata['credit_pack'] ?? null)?->credits;

        if ($creditsToGrant === null || $creditsToGrant <= 0) {
            return; // not a credit checkout
        }

        $workspace = $workspaceId !== null
            ? Workspace::query()->whereKey($workspaceId)->first()
            : Workspace::query()->where('stripe_id', $session['customer'] ?? null)->first();

        if ($workspace === null) {
            Log::warning('GrantCreditPack: workspace not found', [
                'workspace_id' => $workspaceId,
                'customer' => $session['customer'] ?? null,
            ]);

            return;
        }

        $this->credits->creditPack($workspace, $creditsToGrant, (string) ($session['id'] ?? ''));
    }
}
