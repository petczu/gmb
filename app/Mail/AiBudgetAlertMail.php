<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Super-admin alert when system-wide AI spend crosses a share of the global
 * monthly budget (AI_MONTHLY_BUDGET_USD). Plain markdown mail — intentionally
 * NOT part of the tenant-facing editable template system.
 */
class AiBudgetAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $percent,
        public float $spentUsd,
        public float $budgetUsd,
        public string $month,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('AI budget alert: %d%% used (%s)', $this->percent, $this->month),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.ai-budget-alert');
    }
}
