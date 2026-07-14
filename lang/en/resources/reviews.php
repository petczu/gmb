<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => 'Location',
    'col_author' => 'Author',
    'col_review' => 'Review',
    'col_reply' => 'Reply',
    'col_status' => 'Status',
    'col_date' => 'Date',
    'no_reply' => '— no reply —',
    'status_replied' => 'Replied',
    'status_pending' => 'Pending',

    // Filters
    'review_date' => 'Review date',
    'filter_from' => 'From :date',
    'filter_to' => 'To :date',
    'reply_status' => 'Reply status',
    'review_text' => 'Review text',
    'with_text' => 'With text',
    'rating_only' => 'Rating only',

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
    'ai_limit_reached' => 'AI limit reached',
    'ai_limit_body' => 'You’ve used all AI replies this month. Edit manually, or upgrade for a higher limit.',
    'generation_failed' => 'Generation failed',
    'reply_generated' => 'Reply generated',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} Showing 1 review from your email notification|[2,*] Showing :count reviews from your email notification',
    'from_email_clear' => 'Show all reviews',
];
