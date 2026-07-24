<?php

declare(strict_types=1);

return [
    'save' => 'Salvar',
    'close' => 'Fechar',
    'from' => 'De',
    'to' => 'Até',
    'all' => 'Todos',
    'period' => 'Período',
    'location' => 'Local',
    'all_locations' => 'Todos os locais',
    'groups' => 'Grupos',
    'locations' => 'Locais',
    'anonymous' => 'Anônimo',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Últimos 7 dias',
        'last_30' => 'Últimos 30 dias',
        'last_90' => 'Últimos 90 dias',
        'this_month' => 'Este mês',
        'last_month' => 'Mês passado',
        'this_year' => 'Este ano',
        'custom' => 'Intervalo personalizado…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Últimos 7 dias',
        'last_30' => 'Últimos 30 dias',
        'last_90' => 'Últimos 90 dias',
        'this_month' => 'Este mês',
        'last_month' => 'Mês passado',
        'this_year' => 'Este ano',
    ],
];
