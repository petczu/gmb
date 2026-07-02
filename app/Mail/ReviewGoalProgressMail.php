<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailBlocks;

class ReviewGoalProgressMail extends TemplatedMailable
{
    /**
     * @param  'mid'|'recap'  $variant
     * @param  array<string, mixed>  $data  goalProgress() result for "mid", recap() result for "recap"
     */
    public function __construct(
        public string $name,
        public string $variant,
        public array $data,
        public string $reviewsUrl,
        public string $lang = 'en',
    ) {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'review_goal_'.$this->variant;
    }

    protected function templateData(): array
    {
        $intro = $this->variant === 'recap'
            ? __('emails.review_goal.intro_recap', [
                'month' => $this->data['month'] ?? '',
                'actual' => $this->data['total_actual'] ?? 0,
                'goal' => $this->data['total_goal'] ?? 0,
            ], $this->lang)
            : __('emails.review_goal.intro_mid_'.($this->data['status'] ?? 'on_track'), [
                'actual' => $this->data['total_actual'] ?? 0,
                'goal' => $this->data['total_goal'] ?? 0,
                'expected' => $this->data['total_expected'] ?? 0,
            ], $this->lang);

        return [
            'name' => $this->name,
            'intro' => $intro,
            'month' => $this->data['month'] ?? '',
            'url' => $this->reviewsUrl,
        ];
    }

    protected function blocks(): array
    {
        $label = fn (string $key): string => __('emails.review_goal.'.$key, [], $this->lang);
        $items = [];

        foreach (($this->data['rows'] ?? []) as $row) {
            $stats = $this->variant === 'recap'
                ? [
                    ['label' => $label('col_goal'), 'value' => (string) $row['goal']],
                    ['label' => $label('col_got'), 'value' => (string) $row['actual']],
                    ['label' => $label('col_vs_goal'), 'value' => $row['percent'] !== null ? $row['percent'].'%' : '—'],
                    ['label' => $label('col_vs_prev'), 'value' => EmailBlocks::trend((int) $row['delta'])],
                ]
                : [
                    ['label' => $label('col_goal'), 'value' => (string) $row['goal']],
                    ['label' => $label('col_so_far'), 'value' => (string) $row['actual']],
                    ['label' => $label('col_projected'), 'value' => (string) $row['projected']],
                    ['label' => $label('col_pace'), 'value' => e($label('status_'.$row['status']))],
                ];

            $items[] = ['title' => (string) $row['location'], 'rows' => $stats];
        }

        $totalGoal = (int) ($this->data['total_goal'] ?? 0);
        $totalActual = (int) ($this->data['total_actual'] ?? 0);
        $color = $totalGoal > 0 && $totalActual >= $totalGoal ? '#16a34a'
            : (($this->data['status'] ?? '') === 'behind' ? '#f59e0b' : '#1800ff');

        $bar = $totalGoal > 0 ? EmailBlocks::progressBar($totalActual, $totalGoal, $color) : '';

        return ['table' => $bar.($items === [] ? '' : EmailBlocks::stats($items))];
    }
}
