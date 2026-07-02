<?php

declare(strict_types=1);

namespace App\Mail;

class ReviewGoalReachedMail extends TemplatedMailable
{
    public function __construct(
        public string $name,
        public int $goal,
        public string $reviewsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'review_goal_reached';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'goal' => $this->goal, 'url' => $this->reviewsUrl];
    }
}
