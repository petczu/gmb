<?php

declare(strict_types=1);

namespace App\Mail;

class AiLimitReachedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public string $plan,
        public string $plansUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'ai_limit';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'plan' => $this->plan, 'url' => $this->plansUrl];
    }
}
