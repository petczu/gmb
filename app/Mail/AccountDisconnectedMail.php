<?php

declare(strict_types=1);

namespace App\Mail;

class AccountDisconnectedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $accountName,
        public string $locationsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'account_disconnected';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'account' => $this->accountName, 'url' => $this->locationsUrl];
    }
}
