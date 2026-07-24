<?php

declare(strict_types=1);

return [
    'nav' => 'ビジネス情報',
    'title' => 'ビジネス情報',

    'not_configured_title' => 'リスティング管理が設定されていません',
    'not_configured_body' => 'Googleビジネスプロフィールを編集するには、サーバー環境にZERNIO_API_KEYを設定してください。',

    'pick_location' => '店舗',
    'status_live' => 'Google上で公開中',
    'status_suspended' => 'Googleにより停止中',
    'status_disabled' => '無効',
    'status_unverified' => '未確認',

    'section_basics' => 'プロフィール',
    'field_logo' => '店舗ロゴ',
    'field_logo_helper' => 'Google投稿のプレビューに表示されます。空の場合はワークスペースのロゴが使用されます。',
    'field_description' => 'ビジネスの説明',
    'field_description_helper' => 'Googleプロフィールに表示されます。最大750文字です。フォームはGoogleから現在の公開値を読み込みます。',
    'field_phone' => '電話番号',
    'field_website' => 'ウェブサイト',

    'section_hours' => '営業時間',
    'section_hours_desc' => '時間帯ごとに1行です。同じ曜日に2行追加すると分割営業時間になります（例: 昼休み）。',
    'add_hours' => '時間帯を追加',
    'field_day' => '曜日',
    'field_open' => '開店',
    'field_close' => '閉店',

    'day_monday' => '月曜日',
    'day_tuesday' => '火曜日',
    'day_wednesday' => '水曜日',
    'day_thursday' => '木曜日',
    'day_friday' => '金曜日',
    'day_saturday' => '土曜日',
    'day_sunday' => '日曜日',

    'section_special' => '特別営業時間',
    'section_special_desc' => '祝日や例外です。指定した日付については通常の営業時間を上書きします。',

    'section_socials' => 'ソーシャルプロフィール',
    'section_socials_desc' => 'Googleリスティングに表示される、あなたのソーシャルメディアプロフィールへのリンクです。入力されたフィールドのみが公開されます。フィールドを空欄のままにするとGoogle上の現在の値が維持されます。',
    'add_special' => '特別営業時間を追加',
    'field_start_date' => '開始',
    'field_end_date' => '終了',
    'field_closed' => 'これらの日は休業',

    'save' => 'Googleに公開',
    'saved' => 'プロフィールの更新をGoogleに送信しました',
    'save_failed' => '更新に失敗しました',
    'unmatched' => 'この店舗はまだGoogleリスティングと一致させられませんでした。',

    'field_additional_phones' => '追加の電話番号',
    'field_additional_phones_placeholder' => '番号を追加 + Enter',
    'field_additional_phones_help' => 'プロフィールに表示される、最大2つの追加番号です。',
    'field_timezone' => 'タイムゾーン',
    'field_timezone_helper' => '自動返信の営業時間はこのタイムゾーンで解釈されます。連携時に自動検出されます。誤っている場合はここで上書きしてください。',
    'loading_live' => 'Googleから現在のプロフィールデータを読み込み中…',
];
