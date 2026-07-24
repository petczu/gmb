<?php

declare(strict_types=1);

return [
    'nav' => 'アクティビティ',
    'title' => 'アクティビティ',
    'intro' => 'このワークスペースで起きたすべての出来事: 返信、レポート、チームの変更、連携、統合。',

    'empty' => 'アクティビティはまだありません。',
    'empty_desc' => 'このワークスペースで行われた操作がここに表示されます。',
    'system' => 'システム',

    'col_when' => '日時',
    'col_who' => '実行者',
    'col_what' => '内容',
    'col_category' => 'カテゴリ',

    'cat_reviews' => 'レビュー',
    'cat_posts' => '投稿',
    'cat_reports' => 'レポート',
    'cat_team' => 'チーム',
    'cat_locations' => '店舗',
    'cat_integrations' => '連携',

    'action_reply_published' => ':locationでの:authorさんの:rating星レビューへの返信を公開しました',
    'action_report_generated' => ':periodのレポートを生成しました',
    'action_schedule_created' => 'レポートスケジュール「:name」を作成しました（:frequency）',
    'action_schedule_deleted' => 'レポートスケジュール「:name」を削除しました',
    'action_team_member_invited' => ':emailを:roleとして招待しました',
    'action_team_guest_added' => 'ゲスト:member（:email）を追加しました',
    'action_team_member_removed' => ':memberをチームから削除しました',
    'action_team_role_changed' => ':memberの役割を:roleに変更しました',
    'action_location_connected' => '店舗:locationを連携しました',
    'action_location_disconnected' => '店舗:locationの連携を解除しました',
    'action_apikey_created' => 'APIキー「:name」を作成しました（:scopes）',
    'action_apikey_revoked' => 'APIキー「:name」を無効化しました',
    'action_webhook_created' => 'Webhook :urlを追加しました',
    'action_webhook_deleted' => 'Webhook :urlを削除しました',
    'action_review_page_updated' => 'レビュー収集ページ（/r/:slug）を更新しました',
    'action_post_published' => 'Google:type投稿を:locations店舗に公開しました',
    'action_post_scheduled' => 'Google:type投稿を:locations店舗に予約しました',
    'action_listing_updated' => ':locationのGoogleビジネスプロフィールを更新しました',
    'action_competitor_added' => '競合:nameの追跡を開始しました',
    'action_competitor_removed' => '競合:nameの追跡を停止しました',
    'action_competitor_group_created' => '競合グループ「:name」を作成しました',
];
