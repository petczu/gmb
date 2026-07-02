<?php

declare(strict_types=1);

namespace App\Mail;

class PaymentSucceededMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $amount,
        public string $billingUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'payment_succeeded';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'amount' => $this->amount, 'url' => $this->billingUrl];
    }
}
