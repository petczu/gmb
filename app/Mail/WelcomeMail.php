<?php

declare(strict_types=1);

namespace App\Mail;

class WelcomeMail extends TemplatedMailable
{
    public function __construct(public string $name, public string $lang = 'en')
    {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'welcome';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'url' => rtrim((string) config('app.url'), '/').'/'];
    }
}
