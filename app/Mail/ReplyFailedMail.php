<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class ReplyFailedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $businessName,
        public string $authorName,
        public string $snippet,
        public string $reviewsUrl,
        public string $lang = 'en',
        public string $reason = 'error',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'reply_failed';
    }

    protected function templateData(): array
    {
        // Structural failures can't be fixed by retrying, so the detail line
        // explains the cause; transient ones say we'll retry automatically.
        $detailKey = match ($this->reason) {
            'not_found' => 'emails.reply_failed.detail_not_found',
            'unauthorized' => 'emails.reply_failed.detail_unauthorized',
            default => 'emails.reply_failed.detail_retry',
        };

        return [
            'name' => $this->name,
            'business' => $this->businessName,
            'url' => $this->reviewsUrl,
            'detail' => __($detailKey, [], $this->lang),
        ];
    }

    protected function blocks(): array
    {
        return ['table' => EmailBlocks::reviews([
            ['author' => $this->authorName, 'snippet' => $this->snippet],
        ])];
    }
}
