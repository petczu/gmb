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
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'reply_failed';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'business' => $this->businessName, 'url' => $this->reviewsUrl];
    }

    protected function blocks(): array
    {
        return ['table' => EmailBlocks::reviews([
            ['author' => $this->authorName, 'snippet' => $this->snippet],
        ])];
    }
}
