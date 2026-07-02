<?php

declare(strict_types=1);

namespace App\Mail;

class TrialEndingMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public int $days,
        public string $date,
        public string $billingUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'trial_ending';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'days' => $this->days, 'date' => $this->date, 'url' => $this->billingUrl];
    }
}
