<?php

declare(strict_types=1);

return [
    'nav' => 'Activity',
    'title' => 'Activity',
    'intro' => 'Everything that happened in this workspace: replies, reports, team changes, connections and integrations.',

    'empty' => 'No activity yet.',
    'empty_desc' => 'Actions taken in this workspace will show up here.',
    'system' => 'System',

    'col_when' => 'When',
    'col_who' => 'Who',
    'col_what' => 'What happened',
    'col_category' => 'Category',

    'cat_reviews' => 'Reviews',
    'cat_posts' => 'Posts',
    'cat_reports' => 'Reports',
    'cat_team' => 'Team',
    'cat_locations' => 'Locations',
    'cat_integrations' => 'Integrations',

    'action_reply_published' => 'Published a reply to :author\'s :rating-star review at :location',
    'action_report_generated' => 'Generated a report for :period',
    'action_schedule_created' => 'Created report schedule ":name" (:frequency)',
    'action_schedule_deleted' => 'Deleted report schedule ":name"',
    'action_team_member_invited' => 'Invited :email as :role',
    'action_team_guest_added' => 'Added guest :member (:email)',
    'action_team_member_removed' => 'Removed :member from the team',
    'action_team_role_changed' => 'Changed :member\'s role to :role',
    'action_location_connected' => 'Connected location :location',
    'action_location_disconnected' => 'Disconnected location :location',
    'action_apikey_created' => 'Created API key ":name" (:scopes)',
    'action_apikey_revoked' => 'Revoked API key ":name"',
    'action_webhook_created' => 'Added webhook :url',
    'action_webhook_deleted' => 'Removed webhook :url',
    'action_review_page_updated' => 'Updated the review collection page (/r/:slug)',
    'action_post_published' => 'Published a Google :type post to :locations location(s)',
    'action_post_scheduled' => 'Scheduled a Google :type post for :locations location(s)',
    'action_listing_updated' => 'Updated the Google Business profile of :location',
    'action_competitor_added' => 'Started tracking competitor :name',
    'action_competitor_removed' => 'Stopped tracking competitor :name',
];
