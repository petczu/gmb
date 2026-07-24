<?php

declare(strict_types=1);

return [
    'save' => 'Kaydet',
    'close' => 'Kapat',
    'from' => 'Başlangıç',
    'to' => 'Bitiş',
    'all' => 'Tümü',
    'period' => 'Dönem',
    'location' => 'Konum',
    'all_locations' => 'Tüm konumlar',
    'groups' => 'Gruplar',
    'locations' => 'Konumlar',
    'anonymous' => 'Anonim',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'Son 7 gün',
        'last_30' => 'Son 30 gün',
        'last_90' => 'Son 90 gün',
        'this_month' => 'Bu ay',
        'last_month' => 'Geçen ay',
        'this_year' => 'Bu yıl',
        'custom' => 'Özel aralık…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'Son 7 gün',
        'last_30' => 'Son 30 gün',
        'last_90' => 'Son 90 gün',
        'this_month' => 'Bu ay',
        'last_month' => 'Geçen ay',
        'this_year' => 'Bu yıl',
    ],
];
