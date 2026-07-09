<?php

declare(strict_types=1);

return [
    'nav' => 'Business info',
    'title' => 'Business info',

    'not_configured_title' => 'Listing management is not configured',
    'not_configured_body' => 'Set ZERNIO_API_KEY in the server environment to edit Google Business profiles.',

    'pick_location' => 'Location',
    'status_live' => 'Live on Google',
    'status_suspended' => 'Suspended by Google',
    'status_disabled' => 'Disabled',
    'status_unverified' => 'Not verified',

    'section_basics' => 'Profile',
    'field_description' => 'Business description',
    'field_description_helper' => 'Shown on your Google profile. Up to 750 characters. The form loads the current live values from Google.',
    'field_phone' => 'Phone number',
    'field_website' => 'Website',

    'section_hours' => 'Opening hours',
    'section_hours_desc' => 'One row per time range. Add two rows for the same day for split hours (e.g. lunch break).',
    'add_hours' => 'Add time range',
    'field_day' => 'Day',
    'field_open' => 'Opens',
    'field_close' => 'Closes',

    'day_monday' => 'Monday',
    'day_tuesday' => 'Tuesday',
    'day_wednesday' => 'Wednesday',
    'day_thursday' => 'Thursday',
    'day_friday' => 'Friday',
    'day_saturday' => 'Saturday',
    'day_sunday' => 'Sunday',

    'section_special' => 'Special hours',
    'section_special_desc' => 'Holidays and exceptions: these override the regular hours for the given dates.',

    'section_socials' => 'Social profiles',
    'section_socials_desc' => 'Links to your social media profiles, shown on your Google listing. Only filled fields are published; leave a field empty to keep the current value on Google.',
    'add_special' => 'Add special hours',
    'field_start_date' => 'From',
    'field_end_date' => 'Until',
    'field_closed' => 'Closed on these days',

    'save' => 'Publish to Google',
    'saved' => 'Profile update sent to Google',
    'save_failed' => 'Update failed',
    'unmatched' => 'This location could not be matched to a Google listing yet.',

    'field_additional_phones' => 'Additional phone numbers',
    'field_additional_phones_placeholder' => 'add number + Enter',
    'field_additional_phones_help' => 'Up to two extra numbers shown on the profile.',
];
