<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class NewReviewsMail extends TemplatedMailable
{
    /**
     * @param  array<int, array{author: string, rating: int, snippet: string, location?: string}>  $samples
     */
    public function __construct(
        public string $name,
        public int $count,
        public string $locationName,
        public array $samples,
        public string $reviewsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'new_reviews';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'count' => $this->count, 'location' => $this->locationName, 'url' => $this->reviewsUrl];
    }

    protected function blocks(): array
    {
        $items = [];
        foreach ($this->samples as $sample) {
            $items[] = [
                'author' => (string) ($sample['author'] ?? ''),
                'rating' => isset($sample['rating']) ? (int) $sample['rating'] : null,
                'location' => $sample['location'] ?? null,
                'snippet' => (string) ($sample['snippet'] ?? ''),
            ];
        }

        return $items === [] ? [] : ['table' => EmailBlocks::reviews($items)];
    }
}
