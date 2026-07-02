<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSucceededMail;
use App\Mail\SubscriptionCanceledMail;
use App\Mail\SubscriptionResumedMail;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Mail\Mailable;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Sends billing emails in response to Stripe webhooks (delivered via Cashier):
 * a receipt on a successful payment, a "payment failed, action needed" notice
 * with the grace window on a failed payment, and cancel / resume notices when
 * the subscription's cancel-at-period-end flag flips (from the app or the
 * Stripe billing portal).
 */
class SendBillingEmails
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? [];

        $relevant = ['invoice.payment_succeeded', 'invoice.payment_failed', 'customer.subscription.updated'];
        if (! in_array($type, $relevant, true)) {
            return;
        }

        $workspace = Workspace::query()->where('stripe_id', $object['customer'] ?? null)->first();
        if ($workspace === null) {
            return;
        }

        $billingUrl = rtrim((string) config('app.url'), '/').'/billing';

        $build = match ($type) {
            'invoice.payment_succeeded' => fn (string $name, string $lang) => new PaymentSucceededMail(
                $name,
                number_format(((int) ($object['amount_paid'] ?? 0)) / 100, 2).' '.strtoupper((string) ($object['currency'] ?? 'eur')),
                $billingUrl,
                $lang,
            ),
            'invoice.payment_failed' => fn (string $name, string $lang) => new PaymentFailedMail(
                name: $name,
                days: (int) config('services.billing.grace_days', 7),
                billingUrl: $billingUrl,
                lang: $lang,
            ),
            'customer.subscription.updated' => $this->cancellationBuilder($event, $object, $billingUrl),
            default => null,
        };

        if ($build !== null) {
            app(\App\Services\Notifications\NotificationDispatcher::class)->dispatch(
                $workspace,
                \App\Services\Notifications\NotificationCategory::BILLING,
                $build,
            );
        }
    }

    /**
     * Build a cancel / resume notice when (and only when) the subscription's
     * cancel-at-period-end flag changed in this update. Returns null for the
     * many other reasons Stripe fires customer.subscription.updated.
     *
     * @param  array<string, mixed>  $object
     * @return (Closure(string, string): Mailable)|null
     */
    private function cancellationBuilder(WebhookReceived $event, array $object, string $billingUrl): ?Closure
    {
        $previous = $event->payload['data']['previous_attributes'] ?? [];

        if (! array_key_exists('cancel_at_period_end', $previous)) {
            return null;
        }

        if ((bool) ($object['cancel_at_period_end'] ?? false)) {
            $endsAtTimestamp = $object['cancel_at'] ?? $object['current_period_end'] ?? null;

            return function (string $name, string $lang) use ($endsAtTimestamp, $billingUrl): Mailable {
                $endsOn = $endsAtTimestamp
                    ? CarbonImmutable::createFromTimestamp((int) $endsAtTimestamp)->locale($lang)->translatedFormat('j. F Y')
                    : '';

                return new SubscriptionCanceledMail($name, $endsOn, $billingUrl, $lang);
            };
        }

        return fn (string $name, string $lang) => new SubscriptionResumedMail($name, $billingUrl, $lang);
    }
}
