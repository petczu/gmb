<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'Demo Restaurant',
    'demo_period' => 'Performance report · last 30 days',
    'demo_five_star' => '5-star share',
    'demo_summary_label' => 'Executive summary',
    'demo_summary' => 'Demo Restaurant received 38 reviews in the last 30 days (+9 vs the previous period), averaging 4.60★. 84% of reviews were positive and the response rate reached 92%. Guests repeatedly praised the friendly team and fast service.',

    'location' => 'Location',
    'business_multi' => ':name + :count more',
    'compare' => 'Compare',
    'compare_options' => [
        'none' => 'Don’t compare',
        'previous' => 'Previous period',
        'custom' => 'Custom range…',
    ],
    'compare_from' => 'Compare from',
    'compare_to' => 'Compare to',
    'report_language' => 'Report language',

    'content_section' => 'Report content',
    'content_section_desc' => 'Pick a preset, then fine-tune which blocks appear in the report.',
    'preset' => 'Preset',
    'blocks' => 'Blocks',
    'competitors_block_hint' => 'No competitors tracked yet. Add them under Listings > Competitors first.',
    'ai_instructions' => 'AI instructions',
    'ai_instructions_help' => 'Optional guidance for the AI narrative. Most useful for staff names: list your team and any nicknames so mentions are matched to the right person. Saved once and applied to every future report, including scheduled ones.',
    'ai_instructions_placeholder' => 'Our staff: Eva, Alette, Suleyman (also written Suly), Lisa. Merge nicknames into the full name.',
    'ai_improve' => 'Improve with AI',
    'ai_improve_empty' => 'Write a few notes first, then improve them.',
    'ai_improve_rate_limited' => 'Too many attempts, try again later.',
    'ai_improve_done' => 'Instructions improved',
    'ai_improve_failed' => 'Could not improve the instructions, please try again.',

    'schedule_report' => 'Send on a schedule',
    'schedule_heading' => 'Schedule this report',
    'schedule_desc' => 'The current selection (period, location, comparison, blocks) will be emailed as a PDF on a recurring schedule.',
    'schedule_submit' => 'Create schedule',
    'schedule_created' => 'Schedule created',
    'schedule_created_body' => 'Manage it under Reports → Scheduled reports.',

    // Usage line ("N of M AI reports left this month")
    'usage' => ':left of :cap AI reports left this month',

    // Generate modal
    'generate_heading' => 'Generate AI report?',
    'generate_desc' => 'Generate the AI executive summary for the current selection.',
    'generate_desc_left' => 'This uses 1 of your monthly AI reports, :left left.',
    'generate_submit' => 'Generate',

    // Generate notifications
    'report_generated' => 'Report generated',
    'report_generated_body' => 'AI summary is ready, the preview updated. Use Download to save the PDF.',
    'limit_reached' => 'Monthly report limit reached',
    'limit_reached_body' => 'Showing a basic report without AI. Upgrade for a higher monthly limit.',

    // Blade view
    'generate_report' => 'Generate report',
    'generating' => 'Generating…',
    'download_pdf' => 'Download PDF',
    'download_first_tooltip' => 'Generate the report first',
    'building' => 'Building report…',
    'preview_title' => 'Report preview',
];
