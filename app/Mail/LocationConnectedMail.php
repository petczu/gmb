<?php

declare(strict_types=1);

namespace App\Mail;

class LocationConnectedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $location,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'location_connected';
    }

    protected function templateData(): array
    {
        return [
            'name' => $this->name,
            'location' => $this->location,
            'url' => rtrim((string) config('app.url'), '/').'/locations',
        ];
    }
}
