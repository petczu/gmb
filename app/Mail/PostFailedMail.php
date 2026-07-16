<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * A Google post failed to publish (Zernio/Google rejected it or the API call
 * errored). Sent to the Operations recipients so someone retries it. The
 * failed post sits on the Posts calendar with its error.
 */
class PostFailedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $businessName,
        public string $postsUrl,
        public ?string $reason = null,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'post_failed';
    }

    protected function templateData(): array
    {
        $detail = filled($this->reason)
            ? __('emails.post_failed.detail_reason', ['reason' => $this->reason], $this->lang)
            : __('emails.post_failed.detail', [], $this->lang);

        return [
            'name' => $this->name,
            'business' => $this->businessName,
            'url' => $this->postsUrl,
            'detail' => $detail,
        ];
    }
}
