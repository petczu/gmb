<?php

declare(strict_types=1);

return [
    'save' => 'Zapisz',
    'close' => 'Zamknij',
    'from' => 'Od',
    'to' => 'Do',
    'all' => 'Wszystkie',
    'period' => 'Okres',
    'location' => 'Lokalizacja',
    'all_locations' => 'Wszystkie lokalizacje',
    'groups' => 'Grupy',
    'locations' => 'Lokalizacje',
    'anonymous' => 'Anonimowy',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Ostatnie 7 dni',
        'last_30' => 'Ostatnie 30 dni',
        'last_90' => 'Ostatnie 90 dni',
        'this_month' => 'Ten miesiąc',
        'last_month' => 'Poprzedni miesiąc',
        'this_year' => 'Ten rok',
        'custom' => 'Zakres niestandardowy…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Ostatnie 7 dni',
        'last_30' => 'Ostatnie 30 dni',
        'last_90' => 'Ostatnie 90 dni',
        'this_month' => 'Ten miesiąc',
        'last_month' => 'Poprzedni miesiąc',
        'this_year' => 'Ten rok',
    ],
];
