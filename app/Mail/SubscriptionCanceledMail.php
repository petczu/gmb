<?php

declare(strict_types=1);

namespace App\Mail;

class SubscriptionCanceledMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $endsOn,
        public string $billingUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'subscription_canceled';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'date' => $this->endsOn, 'url' => $this->billingUrl];
    }
}
