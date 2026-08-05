<?php

declare(strict_types=1);

namespace App\Mail;

/**
 * Sent to a workspace member who was @-mentioned in a post comment.
 */
class PostMentionMail extends TemplatedMailable
{
    public function __construct(
        public string $mentionerName,
        public string $excerpt,
        public string $postsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'post_mention';
    }

    protected function templateData(): array
    {
        return [
            'name' => $this->mentionerName,
            'excerpt' => $this->excerpt,
            'url' => $this->postsUrl,
        ];
    }
}
