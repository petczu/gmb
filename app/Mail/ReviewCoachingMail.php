<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class ReviewCoachingMail extends TemplatedMailable
{
    /**
     * @param  list<string>  $tips
     */
    public function __construct(
        public string $name,
        public string $intro,
        public array $tips,
        public string $reviewsUrl,
        public string $lang = 'en',
        public int $actual = 0,
        public int $goal = 0,
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'review_coaching';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'intro' => $this->intro, 'url' => $this->reviewsUrl];
    }

    protected function blocks(): array
    {
        $bar = $this->goal > 0 ? EmailBlocks::progressBar($this->actual, $this->goal) : '';
        $list = $this->tips === []
            ? ''
            : EmailBlocks::list(array_map(fn (string $tip): string => e($tip), $this->tips));

        return ['tips' => $bar.$list];
    }
}
