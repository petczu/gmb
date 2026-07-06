<?php

declare(strict_types=1);

return [
    'nav' => 'Posts',
    'title' => 'Google posts',
    'intro' => 'Publish updates, offers, events and photos straight to the Google Business Profiles of your locations. Scheduled posts are delivered by the provider at the chosen time (UTC).',

    'empty' => 'No posts yet.',
    'empty_desc' => 'Create your first post to show news, offers or events on your Google profile.',

    'not_configured_title' => 'Content publishing is not configured',
    'not_configured_body' => 'Set ZERNIO_API_KEY in the server environment to enable Google posts.',

    'col_created' => 'Created',
    'col_type' => 'Type',
    'col_caption' => 'Text',
    'col_locations' => 'Locations',
    'col_status' => 'Status',
    'col_scheduled' => 'Scheduled for',

    'type_update' => 'Update',
    'type_offer' => 'Offer',
    'type_event' => 'Event',
    'type_photo' => 'Photo',

    'status_published' => 'Published',
    'status_scheduled' => 'Scheduled',
    'status_in_progress' => 'Publishing…',
    'status_failed' => 'Failed',

    'create' => 'New post',
    'create_heading' => 'New Google post',
    'submit' => 'Publish',

    'field_type' => 'Post type',
    'field_locations' => 'Locations',
    'field_caption' => 'Text',
    'field_image' => 'Image',
    'field_image_helper' => 'The image must be publicly reachable for Google to fetch it — uploads only work from a public server, not from a local machine.',
    'field_photo_category' => 'Photo category',
    'field_title' => 'Title',
    'field_starts' => 'Starts',
    'field_ends' => 'Ends',
    'field_voucher' => 'Voucher code',
    'field_redeem_url' => 'Redeem link',
    'field_terms_url' => 'Terms & conditions link',
    'field_cta' => 'Call-to-action button',
    'field_cta_url' => 'Button link',
    'field_schedule' => 'Schedule for later',
    'field_schedule_helper' => 'Leave empty to publish immediately. Times are UTC.',

    'cta_none' => 'No button',
    'cta_book' => 'Book',
    'cta_order' => 'Order online',
    'cta_shop' => 'Shop',
    'cta_learn_more' => 'Learn more',
    'cta_sign_up' => 'Sign up',
    'cta_call' => 'Call now',

    'no_locations' => 'Pick at least one location.',
    'unmatched' => 'These locations could not be matched to a Google listing yet:',
    'publish_failed' => 'Publishing failed',
    'published_ok' => 'Post published',
    'scheduled_ok' => 'Post scheduled',

    'delete' => 'Remove',
    'delete_desc' => 'This only removes the entry from this list — it does not delete the post from Google.',
    'deleted' => 'Entry removed',
];
