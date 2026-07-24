<?php

declare(strict_types=1);

return [
    'language' => 'Sprache',
    'select_language' => 'Sprache wählen',
    'save' => 'Speichern',
    'close' => 'Schließen',
    'from' => 'Von',
    'to' => 'Bis',
    'all' => 'Alle',
    'period' => 'Zeitraum',
    'location' => 'Standort',
    'all_locations' => 'Alle Standorte',
    'groups' => 'Gruppen',
    'locations' => 'Standorte',
    'anonymous' => 'Anonym',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Letzte 7 Tage',
        'last_30' => 'Letzte 30 Tage',
        'last_90' => 'Letzte 90 Tage',
        'this_month' => 'Dieser Monat',
        'last_month' => 'Letzter Monat',
        'this_year' => 'Dieses Jahr',
        'custom' => 'Eigener Zeitraum…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Letzte 7 Tage',
        'last_30' => 'Letzte 30 Tage',
        'last_90' => 'Letzte 90 Tage',
        'this_month' => 'Dieser Monat',
        'last_month' => 'Letzter Monat',
        'this_year' => 'Dieses Jahr',
    ],
];
