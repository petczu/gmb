<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'No automations yet',
    'empty_desc' => 'Set up an automation to reply to new reviews automatically, by rating and location.',
    'empty_cta' => 'New automation',

    // Table columns
    'col_name' => 'Name',
    'col_enabled' => 'Enabled',
    'name' => 'Name',
    'enabled' => 'Enabled',
    'col_rating' => 'Rating',
    'rating_any' => 'any',
    'col_reply' => 'Reply',
    'reply_ai' => 'AI: :agent',
    'reply_default' => 'Default message',
    'col_mode' => 'Mode',
    'mode_approval' => 'Approval',
    'mode_auto' => 'Auto-publish',
    'col_scope' => 'Scope',
    'scope_all' => 'All locations',
    'scope_count' => ':count location(s)',

    // Run action
    'run_now' => 'Run now',
    'run_heading' => 'Run this automation now',
    'run_desc' => 'Apply this automation to matching unanswered reviews. Optionally limit it to a review-date period; leave both fields empty to include all.',
    'run_from' => 'Reviews from',
    'run_until' => 'Reviews until',
    'run_title' => 'Ran “:name”',
    'run_queued_title' => '":name" queued',
    'run_queued_body' => 'The run happens in the background. New drafts land in Approvals and auto-published replies appear on the reviews over the next minutes.',
    'run_body' => 'Generated :generated, published :published, queued :queued, skipped :skipped.',

    // Form — Flow section
    'flow_section' => 'Flow',
    'flow_section_desc' => 'When the automation runs and which reviews it applies to.',
    'trigger' => 'Trigger',
    'trigger_new_review' => 'New review on Google',
    'rating_is' => 'Rating is…',
    'rating_helper' => 'Leave all unchecked to apply to any rating.',
    'all_locations' => 'All locations',
    'locations' => 'Locations',
    'all_locations_helper' => 'Acts as a catch-all: automations limited to specific locations take precedence for their locations.',
    'covered_by' => 'already in ":name" (:ratings)',
    'any_rating' => 'any rating',
    'overlap_title' => 'Overlaps with another automation',
    'overlap_body' => 'Also matches the same reviews: :list. Each review is handled by exactly one automation: specific locations win over "All locations", otherwise the older one runs.',
    'respect_working_hours' => 'Respect working hours',
    'respect_working_hours_helper' => 'Reply only during the location\'s open hours.',
    'reply_to_previous' => 'Reply to previous reviews',
    'reply_to_previous_helper' => 'Also handle existing unanswered reviews (counts toward your monthly AI allowance).',
    'approve_before_posting' => 'Approve before posting',
    'approve_before_posting_helper' => 'Off = auto-publish to Google. On = send to Approvals first.',

    // Form — Timing section
    'timing_section' => 'Timing',
    'timing_section_desc' => 'Add a random delay (and optional working hours) so replies post at human-paced, organic times instead of instantly.',
    'reply_delay_min' => 'Minimum delay',
    'reply_delay_max' => 'Maximum delay',
    'minutes_suffix' => 'min',
    'reply_delay_helper' => 'Replies are posted after a random delay between the minimum and maximum, so they look organic. Set both to 0 to post immediately.',
    'reply_delay_max_error' => 'The maximum delay must be greater than or equal to the minimum delay.',
    'working_days' => 'Working days',
    'working_start' => 'Start time',
    'working_end' => 'End time',
    'day_mon' => 'Mon',
    'day_tue' => 'Tue',
    'day_wed' => 'Wed',
    'day_thu' => 'Thu',
    'day_fri' => 'Fri',
    'day_sat' => 'Sat',
    'day_sun' => 'Sun',

    // Form — Content section
    'content_section' => 'Content',
    'content_section_desc' => 'What reply to send.',
    'content_ai_agent' => 'AI agent',
    'content_default_message' => 'Default message',
    'ai_agent' => 'AI agent',
    'default_message' => 'Default message',
];
