<?php

declare(strict_types=1);

return [
    'save' => 'Save',
    'close' => 'Close',
    'from' => 'From',
    'to' => 'To',
    'all' => 'All',
    'period' => 'Period',
    'location' => 'Location',
    'all_locations' => 'All locations',
    'anonymous' => 'Anonymous',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Last 7 days',
        'last_30' => 'Last 30 days',
        'last_90' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'custom' => 'Custom range…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Last 7 days',
        'last_30' => 'Last 30 days',
        'last_90' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
    ],
];
