<?php

declare(strict_types=1);

return [
    'language' => 'اللغة',
    'select_language' => 'اختر اللغة',
    'save' => 'حفظ',
    'close' => 'إغلاق',
    'from' => 'من',
    'to' => 'إلى',
    'all' => 'الكل',
    'period' => 'الفترة',
    'location' => 'الموقع',
    'all_locations' => 'كل المواقع',
    'groups' => 'المجموعات',
    'locations' => 'المواقع',
    'anonymous' => 'مجهول',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => 'آخر 7 أيام',
        'last_30' => 'آخر 30 يومًا',
        'last_90' => 'آخر 90 يومًا',
        'this_month' => 'هذا الشهر',
        'last_month' => 'الشهر الماضي',
        'this_year' => 'هذه السنة',
        'custom' => 'نطاق مخصص…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => 'آخر 7 أيام',
        'last_30' => 'آخر 30 يومًا',
        'last_90' => 'آخر 90 يومًا',
        'this_month' => 'هذا الشهر',
        'last_month' => 'الشهر الماضي',
        'this_year' => 'هذه السنة',
    ],
];
