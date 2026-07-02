<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialRemindersCommand extends Command
{
    protected $signature = 'subscriptions:trial-reminders {--days=3 : Days before trial end to remind}';

    protected $description = 'Email workspace owners whose free trial ends in N days to add a payment method';

    public function handle(LocationBilling $billing): int
    {
        if (! $billing->enabled()) {
            $this->info('Billing disabled, nothing to do.');

            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $target = now()->addDays($days)->startOfDay();
        $billingUrl = rtrim((string) config('app.url'), '/').'/billing';
        $sent = 0;

        foreach (Workspace::query()->with('subscriptions')->get() as $workspace) {
            if (! $billing->onTrial($workspace)) {
                continue;
            }

            $endsAt = $billing->trialEndsAt($workspace);
            if ($endsAt === null || ! $endsAt->copy()->startOfDay()->equalTo($target)) {
                continue;
            }

            app(\App\Services\Notifications\NotificationDispatcher::class)->dispatch(
                $workspace,
                \App\Services\Notifications\NotificationCategory::BILLING,
                fn (string $name, string $lang) => new TrialEndingMail(
                    name: $name,
                    days: $days,
                    date: $endsAt->format('F j, Y'),
                    billingUrl: $billingUrl,
                    lang: $lang,
                ),
            );
            $sent++;
            $this->line("reminded: [{$workspace->slug}]");
        }

        $this->info("Sent {$sent} trial reminder(s).");

        return self::SUCCESS;
    }
}
