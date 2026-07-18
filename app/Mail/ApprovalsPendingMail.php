<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class ApprovalsPendingMail extends TemplatedMailable
{
    /**
     * @param  list<array{location?: string|null, author?: string|null, rating?: int|null, review?: string|null, reply?: string|null}>  $samples
     */
    public function __construct(
        public string $name,
        public int $count,
        public string $approvalsUrl,
        public array $samples = [],
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
        return [
            'name' => $this->name,
            'count' => $this->count,
            'replies' => trans_choice('emails.approvals_pending.reply_word', $this->count, [], $this->lang),
            'url' => $this->approvalsUrl,
        ];
    }

    protected function blocks(): array
    {
        if ($this->samples === []) {
            return [];
        }

        return ['table' => EmailBlocks::approvals(
            $this->samples,
            __('emails.approvals_pending.reply_label', [], $this->lang),
        )];
    }
}
