<?php

declare(strict_types=1);

return [
    'nav' => '投稿',
    'title' => 'Google投稿',

    'empty' => '投稿はまだありません。',
    'empty_desc' => '最初の投稿を作成して、Googleプロフィールにお知らせ、特典、イベントを表示しましょう。',

    'not_configured_title' => 'コンテンツの公開が設定されていません',
    'not_configured_body' => 'Google投稿を有効にするには、サーバー環境にZERNIO_API_KEYを設定してください。',

    'col_created' => '作成日',
    'col_type' => '種類',
    'col_caption' => 'テキスト',
    'col_locations' => '店舗',
    'col_status' => 'ステータス',
    'col_scheduled' => '予約日時',

    'type_update' => '最新情報',
    'type_offer' => '特典',
    'type_event' => 'イベント',
    'type_photo' => '写真',

    'status_published' => '公開済み',
    'status_scheduled' => '予約済み',
    'status_in_progress' => '公開中…',
    'status_failed' => '失敗',
    'status_draft' => '下書き',

    'create' => '新しい投稿',
    'create_heading' => '新しいGoogle投稿',
    'submit' => '公開',

    'field_type' => '投稿の種類',
    'field_locations' => '店舗',
    'field_caption' => 'テキスト',
    'field_image' => '画像',
    'field_image_helper' => 'Googleが取得できるよう、画像は公開状態でアクセスできる必要があります。アップロードは公開サーバーからのみ機能し、ローカルマシンからは機能しません。',
    'field_photo_category' => '写真カテゴリ',
    'field_title' => 'タイトル',
    'field_starts' => '開始',
    'field_ends' => '終了',
    'field_voucher' => 'クーポンコード',
    'field_redeem_url' => '利用リンク',
    'field_terms_url' => '利用規約リンク',
    'field_cta' => 'コールトゥアクションボタン',
    'field_cta_url' => 'ボタンリンク',
    'field_schedule' => '後で予約',
    'field_schedule_helper' => 'すぐに公開する場合は空欄のままにしてください。時刻はUTCです。',

    'cta_none' => 'ボタンなし',
    'cta_book' => '予約',
    'cta_order' => 'オンライン注文',
    'cta_shop' => '購入',
    'cta_learn_more' => '詳細',
    'cta_sign_up' => '登録',
    'cta_call' => '今すぐ電話',

    'no_locations' => '少なくとも1つの店舗を選択してください。',
    'unmatched' => 'これらの店舗はまだGoogleリスティングと一致させられませんでした:',
    'publish_failed' => '公開に失敗しました',
    'published_ok' => '投稿を公開しました',
    'scheduled_ok' => '投稿を予約しました',

    'delete' => '削除',
    'delete_desc' => 'これはこのリストから項目を削除するだけで、Googleから投稿を削除するものではありません。',
    'deleted' => '項目を削除しました',

    // Calendar view
    'view_calendar' => 'カレンダー',
    'view_list' => 'リスト',
    'view_month' => '月',
    'view_week' => '週',
    'today' => '今日',
    'all_locations' => 'すべての店舗',
    'location_plus' => ':name +:count',
    'close' => '閉じる',
    'location_count' => '{1} 1店舗|[2,*] :count店舗',
    'add_post' => '投稿',
    'add_note' => 'メモ',

    // Drafts
    'save_draft' => '下書きを保存',

    // Imported Google posts
    'view' => '表示',
    'duplicate_draft' => '下書きとして複製',
    'duplicated_draft' => '下書きを作成しました',
    'draft_heading' => '下書きを編集',
    'draft_saved' => '下書きを保存しました',
    'draft_delete' => '下書きを削除',
    'draft_delete_desc' => '下書きが削除されます。Googleには何も公開されていません。',
    'draft_deleted' => '下書きを削除しました',

    // Live preview
    'preview_label' => 'プレビュー',
    'preview_business' => 'あなたのビジネス',
    'preview_now' => 'たった今',
    'preview_no_image' => '画像なし',
    'preview_placeholder' => 'ここに投稿テキストが表示されます。',

    // Sticky notes
    'note_placeholder' => 'プライベートメモを入力…',
    'note_color' => 'メモの色',
    'note_tag' => '# タグ',
    'note_delete' => 'メモを削除',
    'note_delete_confirm' => 'このメモを削除しますか？',
    'filter' => 'フィルター',
    'notes_filter' => 'メモ',
    'notes_filter_title' => 'タグ別のメモ',
    'notes_filter_hint' => 'チェックを外したタグはカレンダーから非表示になります。',
    'notes_filter_untagged' => 'タグなし',

    'color_yellow' => 'イエロー',
    'color_orange' => 'オレンジ',
    'color_red' => 'レッド',
    'color_pink' => 'ピンク',
    'color_purple' => 'パープル',
    'color_blue' => 'ブルー',
    'color_teal' => 'ティール',
    'color_green' => 'グリーン',
    'color_gray' => 'グレー',

    // External calendars
    'calendars_button' => '{0} カレンダー|{1} 1個のカレンダー|[2,*] :count個のカレンダー',
    'calendars_connect' => '外部カレンダー',
    'calendars_title' => '外部カレンダー',
    'calendars_empty' => '公開カレンダーをこの表示に重ねられます: 祝日、予約、その他のコンテンツ計画など。',
    'calendars_synced_ago' => ':agoに同期',
    'calendars_refresh' => '今すぐ同期',
    'calendars_synced' => 'カレンダーを同期しました',
    'calendars_sync_failed' => '一部のカレンダーの同期に失敗しました',
    'calendar_add' => '外部カレンダーを追加',
    'calendar_add_submit' => 'カレンダーを追加',
    'calendar_name' => '名前',
    'calendar_name_placeholder' => '例: オーストリアの祝日',
    'calendar_url' => 'ICSリンク',
    'calendar_url_helper' => '公開iCal/ICSフィードのURLです。Googleカレンダーの場合: 設定、次に「カレンダーの統合」、次に「iCal形式の公開URL」。',
    'calendar_color' => '色',
    'calendar_added' => 'カレンダーを追加しました',
    'calendar_events_count' => '{0} フィードにイベントが見つかりませんでした。|{1} 1件のイベントをインポートしました。|[2,*] :count件のイベントをインポートしました。',
    'calendar_sync_error' => 'カレンダーを追加しましたが、フィードを同期できませんでした',
    'calendar_delete' => 'カレンダーを削除',
    'calendar_delete_confirm' => 'このカレンダーとそのイベントを表示から削除しますか？',
];
