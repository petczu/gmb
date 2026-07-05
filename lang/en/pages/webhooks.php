<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'Webhooks are a Pro feature',
    'pro_only_body' => 'Get a signed HTTP POST the moment a review comes in, a reply is published, a goal is reached or an anomaly is detected. Upgrade to Pro to add endpoints.',
    'see_plans' => 'See plans',

    'intro' => 'We POST a signed JSON payload to your endpoint on each subscribed event, with retries. Verify the X-Webhook-Signature header against your endpoint secret.',

    'docs_link' => 'Webhook documentation',
    'empty' => 'No webhook endpoints yet.',
    'col_url' => 'URL',
    'col_events' => 'Events',
    'col_active' => 'Active',
    'col_last' => 'Last fired',

    'create' => 'Add endpoint',
    'create_heading' => 'Add webhook endpoint',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'saved' => 'Endpoint saved',
    'created' => 'Endpoint added',
    'deleted' => 'Endpoint deleted',

    'field_name' => 'Name (optional)',
    'field_url' => 'Endpoint URL',
    'field_events' => 'Events',
    'field_active' => 'Active',

    'secret' => 'Secret',
    'secret_heading' => 'Signing secret',
    'secret_desc' => 'Use this to verify the payload signature.',
    'signature_hint' => 'Each request is signed:',

    'deliveries' => 'Deliveries',
    'deliveries_heading' => 'Recent deliveries',
    'no_deliveries' => 'No deliveries yet.',
    'attempts' => 'attempts',
    'resend' => 'Resend',
    'resent' => 'Delivery re-queued',
    'status_pending' => 'Pending',
    'status_success' => 'Delivered',
    'status_failed' => 'Failed',

    'event_review_created' => 'New review',
    'event_reply_published' => 'Reply published',
    'event_goal_reached' => 'Goal reached',
    'event_anomaly_detected' => 'Anomaly detected',
];
