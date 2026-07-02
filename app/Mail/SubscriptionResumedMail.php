<?php

declare(strict_types=1);

namespace App\Mail;

class SubscriptionResumedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $billingUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'subscription_resumed';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'url' => $this->billingUrl];
    }
}
