<?php

declare(strict_types=1);

return [
    'language' => 'Idioma',
    'select_language' => 'Seleccionar idioma',
    'save' => 'Guardar',
    'close' => 'Cerrar',
    'from' => 'Desde',
    'to' => 'Hasta',
    'all' => 'Todo',
    'period' => 'Periodo',
    'location' => 'Ubicación',
    'all_locations' => 'Todas las ubicaciones',
    'groups' => 'Grupos',
    'locations' => 'Ubicaciones',
    'anonymous' => 'Anónimo',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Últimos 7 días',
        'last_30' => 'Últimos 30 días',
        'last_90' => 'Últimos 90 días',
        'this_month' => 'Este mes',
        'last_month' => 'Mes pasado',
        'this_year' => 'Este año',
        'custom' => 'Rango personalizado…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Últimos 7 días',
        'last_30' => 'Últimos 30 días',
        'last_90' => 'Últimos 90 días',
        'this_month' => 'Este mes',
        'last_month' => 'Mes pasado',
        'this_year' => 'Este año',
    ],
];
