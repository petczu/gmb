<?php

declare(strict_types=1);

return [
    'save' => '保存',
    'close' => '閉じる',
    'from' => '開始',
    'to' => '終了',
    'all' => 'すべて',
    'period' => '期間',
    'location' => '店舗',
    'all_locations' => 'すべての店舗',
    'groups' => 'グループ',
    'locations' => '店舗',
    'anonymous' => '匿名',

    // Shared period option set (Dashboard + Reports + Schedules)
    'periods' => [
        'last_7' => '過去7日間',
        'last_30' => '過去30日間',
        'last_90' => '過去90日間',
        'this_month' => '今月',
        'last_month' => '先月',
        'this_year' => '今年',
        'custom' => 'カスタム期間…',
    ],

    // Same set minus the custom range (used by scheduled reports)
    'periods_no_custom' => [
        'last_7' => '過去7日間',
        'last_30' => '過去30日間',
        'last_90' => '過去90日間',
        'this_month' => '今月',
        'last_month' => '先月',
        'this_year' => '今年',
    ],
];
