<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class NegativeReviewMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $businessName,
        public string $authorName,
        public int $rating,
        public string $snippet,
        public string $reviewsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'negative_review';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'business' => $this->businessName, 'rating' => $this->rating, 'url' => $this->reviewsUrl];
    }

    protected function blocks(): array
    {
        return ['table' => EmailBlocks::reviews([
            ['author' => $this->authorName, 'rating' => $this->rating, 'snippet' => $this->snippet],
        ])];
    }
}
