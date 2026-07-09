<?php

declare(strict_types=1);

namespace App\Mail;

class BetaReceivedMail extends TemplatedMailable
{
    public function __construct(public string $name, public string $lang = 'en')
    {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'beta_received';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name];
    }
}
