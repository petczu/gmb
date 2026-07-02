<?php

declare(strict_types=1);

namespace App\Mail;

class InviteMail extends TemplatedMailable
{
    public function __construct(
        public string $inviterName,
        public string $workspaceName,
        public string $acceptUrl,
        public string $role,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'invite';
    }

    protected function templateData(): array
    {
        return [
            'inviter' => $this->inviterName,
            'workspace' => $this->workspaceName,
            'role' => $this->role,
            'url' => $this->acceptUrl,
        ];
    }
}
