<?php

declare(strict_types=1);

return [
    'title' => 'AI auto-reply rules',
    'section' => ':stars  ·  :rating-star reviews',
    'enabled' => 'Auto-reply enabled',
    'mode' => 'Mode',
    'mode_auto' => 'Auto-publish',
    'mode_draft' => 'Draft for approval',
    'tone' => 'Tone / template',
    'tone_placeholder_positive' => 'e.g. Warm and thankful.',
    'tone_placeholder_negative' => 'e.g. Apologize and offer to make it right.',
    'instruction' => 'Extra instruction (optional)',
    'language' => 'Language',
    'language_placeholder' => 'Auto-detect from review',
    'save_rules' => 'Save rules',
    'rules_saved' => 'Auto-reply rules saved',

    // Blade intro
    'intro' => 'Configure how the AI replies to each star rating. <strong>Auto-publish</strong> posts the reply to Google immediately; <strong>Draft for approval</strong> sends it to the Approvals queue first. Each generation counts toward your plan\'s monthly AI allowance.',
];
