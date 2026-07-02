<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class ReviewAnomalyMail extends TemplatedMailable
{
    /**
     * @param  list<array{location: string, type: string, detail: array<string, int|float>}>  $anomalies
     */
    public function __construct(
        public string $name,
        public array $anomalies,
        public string $reviewsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'review_anomaly';
    }

    protected function templateData(): array
    {
        return ['name' => $this->name, 'count' => count($this->anomalies), 'url' => $this->reviewsUrl];
    }

    protected function blocks(): array
    {
        $lines = [];
        foreach ($this->anomalies as $anomaly) {
            $lines[] = '<strong>'.e($anomaly['location']).'</strong>: '
                .e(__('emails.review_anomaly.'.$anomaly['type'], $anomaly['detail'], $this->lang));
        }

        return $lines === [] ? [] : ['items' => EmailBlocks::list($lines)];
    }
}
