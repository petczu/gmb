<?php

declare(strict_types=1);

return [
    'empty_heading' => 'スケジュールされたレポートがありません',
    'empty_desc' => '定期的なPDFレポートが自動的に生成されメールで送信されるようスケジュールしましょう。',
    'empty_cta' => '新しいスケジュール',

    // Table
    'col_name' => '名前',
    'col_enabled' => '有効',
    'name' => '名前',
    'enabled' => '有効',
    'frequency_weekly' => '毎週 · :day',
    'frequency_monthly' => '毎月 · :day日',
    'col_period' => '期間',
    'col_recipients' => '受信者',
    'recipients_all' => 'すべてのメンバー',
    'recipients_count' => ':count件のメール',
    'col_last_sent' => '最終送信',
    'never' => 'なし',

    // Weekday abbreviations
    'mon' => '月',
    'tue' => '火',
    'wed' => '水',
    'thu' => '木',
    'fri' => '金',
    'sat' => '土',
    'sun' => '日',

    // Send-now action
    'send_now' => '今すぐ送信',
    'send_now_desc' => 'このレポートを今すぐ生成してメールで送信します。',
    'report_queued' => 'レポートをキューに追加しました',
    'report_queued_body' => 'まもなく生成され、メールで送信されます。',

    // Form
    'schedule_section' => 'スケジュール',
    'default_name' => '月次パフォーマンスレポート',
    'frequency' => '頻度',
    'frequency_monthly_opt' => '毎月',
    'frequency_weekly_opt' => '毎週',
    'day_of_month' => '日付',
    'day_of_month_helper' => '1〜28',
    'day_of_week' => '曜日',
    'monday' => '月曜日',
    'tuesday' => '火曜日',
    'wednesday' => '水曜日',
    'thursday' => '木曜日',
    'friday' => '金曜日',
    'saturday' => '土曜日',
    'sunday' => '日曜日',

    'contents_section' => 'レポートの内容',
    'period' => '期間',
    'language' => 'レポートの言語',
    'location' => '店舗',
    'compare' => '前期間と比較',
    'recipients' => '受信者（メール）',
    'recipients_include' => '送信先',
    'recipients_exclude' => '除外',
    'recipients_emails' => '追加のメール',
    'recipients_all' => 'すべてのメンバー',
    'recipients_none' => '例外なし',
    'recipients_placeholder' => 'メールを追加 + Enter',
    'recipients_helper' => 'すべてのワークスペースメンバーに送信するには空欄のままにしてください。',
];
