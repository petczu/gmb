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
        // 'not_found' = the review is gone on Google; suggesting a retry
        // would be misleading, so the detail line explains it instead.
        $detail = $this->reason === 'not_found'
            ? __('emails.reply_failed.detail_not_found', [], $this->lang)
            : __('emails.reply_failed.detail', [], $this->lang);

        return ['name' => $this->name, 'business' => $this->businessName, 'url' => $this->reviewsUrl, 'detail' => $detail];
    }

    protected function blocks(): array
    {
        return ['table' => EmailBlocks::reviews([
            ['author' => $this->authorName, 'snippet' => $this->snippet],
        ])];
    }
}
