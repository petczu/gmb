<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class LocationSyncedMail extends TemplatedMailable
{
    /**
     * @param  list<array{name: string, count: int, rating: ?float}>  $locations
     */
    public function __construct(
        public string $name,
        public array $locations,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'location_synced';
    }

    protected function templateData(): array
    {
        return [
            'name' => $this->name,
            'count' => count($this->locations),
            'url' => rtrim((string) config('app.url'), '/').'/reviews',
        ];
    }

    protected function blocks(): array
    {
        return [
            'items' => EmailBlocks::list(array_map(
                fn (array $location): string => '<strong>'.e($location['name']).'</strong>: '
                    .number_format($location['count']).' reviews'
                    .($location['rating'] !== null ? ' · '.number_format($location['rating'], 1).'★' : ''),
                $this->locations,
            )),
        ];
    }
}
