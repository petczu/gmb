<?php

declare(strict_types=1);

return [
    'empty_heading' => 'No locations connected',
    'empty_desc' => 'Connect a Google Business Profile location to start pulling in its reviews.',
    'empty_cta' => 'Connect location',

    'col_location' => 'Location',
    'col_reviews' => 'Reviews',
    'col_last_synced' => 'Last synced',
    'syncing' => 'Importing reviews…',
    'syncing_hint' => 'The first import from Google can take a few minutes. You will get an email when it is done.',
    'sync_failed' => 'Sync failed',

    'disconnect' => 'Disconnect',
    'disconnect_heading' => 'Disconnect location',
    'disconnect_desc' => 'Stop tracking this location and remove its synced reviews from this workspace.',
    'disconnected' => 'Location disconnected',

    // ListLocations header actions
    'add_location' => 'Add location',
    'add_demo_data' => 'Add demo data',
    'demo_added' => 'Demo data added',
    'edit_info' => 'Edit info',

    // Bulk hours editing
    'bulk_hours' => 'Edit hours',

    // Location groups (filter + organize)
    'group' => 'Group',
    'groups' => 'Groups',
    'groups_heading' => 'Location groups',
    'groups_desc' => 'Group your locations into clusters. A group filters this list and appears in the dashboard and report location filter.',
    'groups_save' => 'Save groups',
    'groups_saved' => 'Groups saved',
    'group_add' => 'Add group',
    'group_name' => 'Group name',
    'group_locations' => 'Locations',
    'bulk_hours_heading' => 'Edit hours on selected locations',
    'bulk_hours_desc' => 'The sections you switch on below are pushed to every selected Google profile. A pushed section replaces that profile\'s current set, sections left off stay untouched.',
    'bulk_hours_submit' => 'Apply to selected',
    'bulk_hours_apply' => 'Apply this section',
    'bulk_hours_regular' => 'Opening hours',
    'bulk_hours_regular_desc' => 'The weekly schedule. Days without a row show as closed on Google.',
    'bulk_hours_special' => 'Special hours',
    'bulk_hours_special_desc' => 'Exceptions for specific dates: holidays, shortened days or extra closures.',
    'bulk_hours_add_row' => 'Add day',
    'bulk_hours_holidays' => 'Add from your external calendars',
    'bulk_hours_holidays_help' => 'Pick dates from the calendars connected on the Posts page, each becomes a closed day.',
    'bulk_hours_locations' => 'Locations',
    'bulk_hours_apply_on' => 'Apply from date',
    'bulk_hours_apply_on_help' => 'Leave empty to apply now. With a date, the change is pushed to Google early in the morning of that day (UTC).',
    'bulk_hours_scheduled' => 'Hours update scheduled for :date',
    'bulk_hours_scheduled_body' => '{1} It will be applied to 1 location automatically.|[2,*] It will be applied to :count locations automatically.',
    'bulk_hours_nothing' => 'Nothing to apply: switch on at least one section and add rows.',
    'bulk_hours_unmatched' => 'not matched to a Google listing',
    'bulk_hours_done' => '{1} Hours updated on 1 location.|[2,*] Hours updated on :count locations.',
    'bulk_hours_partial' => '{0} Hours could not be updated.|{1} Hours updated on 1 location, with problems:|[2,*] Hours updated on :count locations, with problems:',
];
