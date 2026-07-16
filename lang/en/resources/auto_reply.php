<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'Nothing to approve',
    'empty_desc' => 'When automations draft replies that need approval, they show up here.',

    // Columns
    'col_location' => 'Location',
    'col_author' => 'Author',
    'col_rating' => 'Rating',
    'col_review' => 'Review',
    'col_ai_reply' => 'AI reply',
    'col_status' => 'Status',
    'col_source' => 'Source',
    'col_generated' => 'Generated',
    'source_ai' => 'AI',
    'source_template' => 'Template',

    // Statuses
    'status_pending' => 'Pending',
    'status_scheduled' => 'Scheduled',
    'status_published' => 'Published',
    'status_skipped' => 'Skipped',
    'status_failed' => 'Failed',
    'status_indicator' => 'Status: :status',
    'scheduled_for' => 'Posts :time',

    // Actions
    'approve' => 'Approve & publish',
    'approve_publish' => 'Approve & publish',
    'edit_publish' => 'Edit & publish',
    'review_reply' => 'Review & reply',
    'reply' => 'Reply',
    'reject' => 'Reject',

    // Filters
    'filter_date' => 'Review date',
    'filter_from' => 'From :date',
    'filter_to' => 'Until :date',

    // Notifications
    'reply_published' => 'Reply published',

    'approve_selected' => 'Approve & publish selected',
    'reject_selected' => 'Reject selected',
    'bulk_approve_confirm' => 'Publish all selected replies to Google? They are queued and go out automatically over the next minutes.',
    'bulk_reject_confirm' => 'Reject all selected drafts?',
    'bulk_queued' => ':count replies queued for publishing',
    'bulk_queued_body' => 'They publish automatically over the next minutes. Any failure shows up under the Failed filter with the reason.',
    'bulk_rejected' => ':count drafts rejected',
    'publish_failed_title' => 'Publishing failed',
    'publish_not_found' => 'Google says this review no longer exists. It may have been deleted by its author, or the location was reconnected under a new account. The draft was marked as failed.',
    'publish_error' => 'The reply could not be published. The draft was marked as failed: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Google couldn’t find this review or location to reply to. It may have been removed, or replies aren’t available for this location.',
    'error_rate_limited' => 'Google is limiting how fast replies can be posted. It will be retried automatically.',
    'error_unauthorized' => 'The Google connection isn’t authorized to reply here. Reconnect the account and try again.',
    'error_generic' => 'The reply couldn’t be posted. Please try again later.',
    'draft_rejected' => 'Draft rejected',

    // Scheduled items
    'post_now' => 'Post now',
    'post_now_confirm' => 'The reply is published to Google immediately, skipping its scheduled time.',
    'post_now_queued' => 'Reply queued for publishing',
    'post_now_queued_body' => 'It goes out within the next few minutes.',
    'cancel_scheduled' => 'Cancel',
    'cancel_scheduled_confirm' => 'Cancel this scheduled reply? It will not be posted.',
    'schedule_cancelled' => 'Scheduled reply cancelled',

    // List tabs
    'tab_pending' => 'Needs approval',
    'tab_all' => 'All',

    // Scheduled-tab bulk labels
    'publish_now_selected' => 'Publish selected now',
    'bulk_publish_now_confirm' => 'The selected replies skip their scheduled time and go out within the next few minutes.',
    'cancel_scheduled_selected' => 'Cancel scheduling',
];
