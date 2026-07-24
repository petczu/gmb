<?php

declare(strict_types=1);

return [
    'save' => 'Opslaan',
    'close' => 'Sluiten',
    'from' => 'Van',
    'to' => 'Tot',
    'all' => 'Alle',
    'period' => 'Periode',
    'location' => 'Locatie',
    'all_locations' => 'Alle locaties',
    'groups' => 'Groepen',
    'locations' => 'Locaties',
    'anonymous' => 'Anoniem',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Laatste 7 dagen',
        'last_30' => 'Laatste 30 dagen',
        'last_90' => 'Laatste 90 dagen',
        'this_month' => 'Deze maand',
        'last_month' => 'Vorige maand',
        'this_year' => 'Dit jaar',
        'custom' => 'Aangepaste periode…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Laatste 7 dagen',
        'last_30' => 'Laatste 30 dagen',
        'last_90' => 'Laatste 90 dagen',
        'this_month' => 'Deze maand',
        'last_month' => 'Vorige maand',
        'this_year' => 'Dit jaar',
    ],
];
