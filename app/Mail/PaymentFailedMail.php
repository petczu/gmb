<?php

declare(strict_types=1);

namespace App\Mail;

class PaymentFailedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public int $days,
        public string $billingUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'payment_failed';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'days' => $this->days, 'url' => $this->billingUrl];
    }
}
