<?php

declare(strict_types=1);

namespace App\Mail;

class ApprovalsPendingMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public int $count,
        public string $approvalsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'approvals_pending';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'count' => $this->count, 'url' => $this->approvalsUrl];
    }
}
