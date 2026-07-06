<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Support\Facades\URL;

/**
 * One step of the onboarding email series. The template key is dynamic (all
 * steps share the shape: name + app url + signed unsubscribe url).
 */
class DripMail extends TemplatedMailable
{
    public function __construct(
        protected string $key,
        public string $name,
        public int $userId,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return $this->key;
    }

    protected function templateData(): array
    {
        return [
            'name' => $this->name,
            'url' => rtrim((string) config('app.url'), '/').'/',
            'unsubscribe_url' => URL::signedRoute('unsubscribe.product', ['user' => $this->userId]),
        ];
    }
}
