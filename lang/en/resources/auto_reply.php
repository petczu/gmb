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
    'status_published' => 'Published',
    'status_skipped' => 'Skipped',
    'status_failed' => 'Failed',
    'status_indicator' => 'Status: :status',

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
    'bulk_approve_confirm' => 'Publish all selected replies to Google?',
    'bulk_reject_confirm' => 'Reject all selected drafts?',
    'bulk_approved' => ':count replies published',
    'bulk_rejected' => ':count drafts rejected',
    'publish_failed_title' => 'Publishing failed',
    'publish_not_found' => 'Google says this review no longer exists. It may have been deleted by its author, or the location was reconnected under a new account. The draft was marked as failed.',
    'publish_error' => 'The reply could not be published. The draft was marked as failed: :message',
    'bulk_result' => ':published published, :failed failed. Failed drafts keep the error in the Failed filter.',
    'draft_rejected' => 'Draft rejected',
];
