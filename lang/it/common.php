<?php

declare(strict_types=1);

return [
    'language' => 'Lingua',
    'select_language' => 'Seleziona la lingua',
    'save' => 'Salva',
    'close' => 'Chiudi',
    'from' => 'Dal',
    'to' => 'Al',
    'all' => 'Tutti',
    'period' => 'Periodo',
    'location' => 'Sede',
    'all_locations' => 'Tutte le sedi',
    'groups' => 'Gruppi',
    'locations' => 'Sedi',
    'anonymous' => 'Anonimo',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Ultimi 7 giorni',
        'last_30' => 'Ultimi 30 giorni',
        'last_90' => 'Ultimi 90 giorni',
        'this_month' => 'Questo mese',
        'last_month' => 'Il mese scorso',
        'this_year' => 'Quest’anno',
        'custom' => 'Intervallo personalizzato…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Ultimi 7 giorni',
        'last_30' => 'Ultimi 30 giorni',
        'last_90' => 'Ultimi 90 giorni',
        'this_month' => 'Questo mese',
        'last_month' => 'Il mese scorso',
        'this_year' => 'Quest’anno',
    ],
];
