<?php

declare(strict_types=1);

namespace App\Mail;

class BetaApprovedMail extends TemplatedMailable
{
    public function __construct(public string $name, public string $lang = 'en')
    {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'beta_approved';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'url' => rtrim((string) config('app.url'), '/').'/'];
    }
}
