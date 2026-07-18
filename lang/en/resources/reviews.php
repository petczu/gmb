<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Location',
    'col_author' => 'Author',
    'col_review' => 'Review',
    'col_reply' => 'Reply',
    'col_status' => 'Status',
    'col_replied_by' => 'Replied by',
    'col_date' => 'Date',
    'replied_ai' => 'AI',
    'replied_human' => 'Team',
    'replied_assistant' => 'Assistant',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '— no reply —',
    'status_replied' => 'Replied',
    'status_pending' => 'Pending',
    'status_scheduled' => 'Scheduled',
    'status_failed' => 'Failed',
    'reply_draft_note' => 'Draft, not posted yet',

    // Filters
    'review_date' => 'Review date',
    'filter_from' => 'From :date',
    'filter_to' => 'To :date',
    'reply_status' => 'Reply status',
    'review_text' => 'Review text',
    'with_text' => 'With text',
    'rating_only' => 'Rating only',
    'photos' => 'Photos',
    'with_photos' => 'With photos',
    'without_photos' => 'Without photos',

    // Reply action
    'edit_reply' => 'Edit reply',
    'save_reply' => 'Save',
    'reply' => 'Reply',
    'reply_to_review' => 'Reply to review',
    'no_written_review' => 'No written review, rating only.',
    'translated_by_google' => '🌐 Translated by Google',
    'ai_agent' => 'AI agent',
    'default_agent' => 'Default agent',
    'your_reply' => 'Your reply',
    'generate_with_ai' => 'Generate with AI',
    'generate' => 'Generate',
    'generating' => 'Generating your reply…',
    'cancel' => 'Cancel',
    'add_emoji' => 'Add emoji',
    'show_translation' => 'Show translation (:language)',
    'translation_label' => 'Translation (:language)',
    'translation_failed' => 'Translation failed',
    'hide_emoji' => 'Hide emoji',
    'delete_reply' => 'Delete reply',
    'delete_reply_desc' => 'This removes the reply from Google. The review itself is not affected.',
    'delete_confirm' => 'Delete',
    'submit_heading' => 'Publish your reply?',
    'submit_desc' => 'This posts your reply publicly on Google, visible to everyone who sees the review.',
    'submit_confirm' => 'Publish',

    // AI cost hints
    'cost_generic' => 'This generates a reply with AI.',
    'cost_all_used' => 'You\'ve used all your AI replies this month. Top up a pack, upgrade, or write the reply manually.',
    'cost_credit' => 'This uses 1 credit (:count left).',
    'cost_monthly' => 'This uses 1 of your monthly AI replies, :count left.',

    // Notifications
    'reply_deleted' => 'Reply deleted',
    'no_changes' => 'No changes to save',
    'reply_published' => 'Reply published',
    'reply_failed' => 'Reply could not be posted',
    'ai_limit_reached' => 'AI limit reached',
    'ai_limit_body' => 'You’ve used all AI replies this month. Edit manually, or upgrade for a higher limit.',
    'generation_failed' => 'Generation failed',
    'reply_generated' => 'Reply generated',
    'retry' => 'Retry',
    'retry_heading' => 'Retry this reply?',
    'retry_desc' => 'We’ll try again: re-post the drafted reply, or regenerate it if the AI step failed.',
    'retry_queued' => 'Reply queued again',
    'retry_nothing' => 'Nothing to retry. Reply manually instead.',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'All',
    'tab_needs_approval' => 'Needs approval',
    'tab_scheduled' => 'Scheduled',
    'tab_published' => 'Published',
    'tab_failed' => 'Failed',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Showing 1 review from your email notification|[2,*] Showing :count reviews from your email notification',
    'from_email_clear' => 'Show all reviews',
];
