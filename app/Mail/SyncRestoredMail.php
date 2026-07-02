<?php

declare(strict_types=1);

namespace App\Mail;

class SyncRestoredMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $accountName,
        public string $dashboardUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'sync_restored';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'account' => $this->accountName, 'url' => $this->dashboardUrl];
    }
}
