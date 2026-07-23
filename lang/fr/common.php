<?php

declare(strict_types=1);

return [
    'save' => 'Enregistrer',
    'close' => 'Fermer',
    'from' => 'Du',
    'to' => 'Au',
    'all' => 'Tout',
    'period' => 'Période',
    'location' => 'Établissement',
    'all_locations' => 'Tous les établissements',
    'groups' => 'Groupes',
    'locations' => 'Établissements',
    'anonymous' => 'Anonyme',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => '7 derniers jours',
        'last_30' => '30 derniers jours',
        'last_90' => '90 derniers jours',
        'this_month' => 'Ce mois-ci',
        'last_month' => 'Le mois dernier',
        'this_year' => 'Cette année',
        'custom' => 'Période personnalisée…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => '7 derniers jours',
        'last_30' => '30 derniers jours',
        'last_90' => '90 derniers jours',
        'this_month' => 'Ce mois-ci',
        'last_month' => 'Le mois dernier',
        'this_year' => 'Cette année',
    ],
];
