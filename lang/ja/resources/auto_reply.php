<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => '承認するものがありません',
    'empty_desc' => '自動化が承認の必要な返信を下書きすると、ここに表示されます。',

    // Columns
    'col_location' => '店舗',
    'col_author' => '投稿者',
    'col_rating' => '評価',
    'col_review' => 'レビュー',
    'col_ai_reply' => 'AI返信',
    'col_status' => 'ステータス',
    'col_source' => 'ソース',
    'col_generated' => '生成',
    'source_ai' => 'AI',
    'source_template' => 'テンプレート',

    // Statuses
    'status_pending' => '保留中',
    'status_scheduled' => '予約済み',
    'status_published' => '公開済み',
    'status_skipped' => 'スキップ済み',
    'status_failed' => '失敗',
    'status_indicator' => 'ステータス: :status',
    'scheduled_for' => ':timeに投稿',

    // Actions
    'approve' => '承認して公開',
    'approve_publish' => '承認して公開',
    'edit_publish' => '編集して公開',
    'review_reply' => '確認して返信',
    'reply' => '返信',
    'reject' => '却下',

    // Filters
    'filter_date' => 'レビュー日',
    'filter_from' => ':dateから',
    'filter_to' => ':dateまで',

    // Notifications
    'reply_published' => '返信を公開しました',

    'approve_selected' => '選択した項目を承認して公開',
    'reject_selected' => '選択した項目を却下',
    'bulk_approve_confirm' => '選択したすべての返信をGoogleに公開しますか？キューに追加され、数分かけて自動的に送信されます。',
    'bulk_reject_confirm' => '選択したすべての下書きを却下しますか？',
    'bulk_queued' => ':count件の返信を公開のためにキューに追加しました',
    'bulk_queued_body' => '数分かけて自動的に公開されます。失敗した場合は理由とともに失敗フィルターに表示されます。',
    'bulk_rejected' => ':count件の下書きを却下しました',
    'publish_failed_title' => '公開に失敗しました',
    'publish_not_found' => 'Googleによると、このレビューはもう存在しません。投稿者によって削除されたか、店舗が新しいアカウントで再連携された可能性があります。下書きは失敗としてマークされました。',
    'publish_error' => '返信を公開できませんでした。下書きは失敗としてマークされました: :message',

    // Short, human-readable stored failure reasons (shown on the Failed tab)
    'error_not_found' => 'Googleは返信対象のこのレビューまたは店舗を見つけられませんでした。削除されたか、この店舗では返信を利用できない可能性があります。',
    'error_rate_limited' => 'Googleが返信の投稿速度を制限しています。自動的に再試行されます。',
    'error_unauthorized' => 'このGoogle連携はここへの返信を承認されていません。アカウントを再連携して再度お試しください。',
    'error_generic' => '返信を投稿できませんでした。しばらくしてから再度お試しください。',
    'draft_rejected' => '下書きを却下しました',

    // Scheduled items
    'post_now' => '今すぐ投稿',
    'post_now_confirm' => '予約時刻をスキップして、返信をすぐにGoogleに公開します。',
    'post_now_queued' => '返信を公開のためにキューに追加しました',
    'post_now_queued_body' => '数分以内に送信されます。',
    'cancel_scheduled' => 'キャンセル',
    'cancel_scheduled_confirm' => 'この予約済みの返信をキャンセルしますか？投稿されません。',
    'schedule_cancelled' => '予約済みの返信をキャンセルしました',

    // List tabs
    'tab_pending' => '承認が必要',
    'tab_all' => 'すべて',

    // Scheduled-tab bulk labels
    'publish_now_selected' => '選択した項目を今すぐ公開',
    'bulk_publish_now_confirm' => '選択した返信は予約時刻をスキップし、数分以内に送信されます。',
    'cancel_scheduled_selected' => '予約をキャンセル',
];
