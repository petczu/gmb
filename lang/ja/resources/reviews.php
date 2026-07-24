<?php

declare(strict_types=1);

return [
    // Columns
    'col_location' => '店舗',
    'col_rating' => '評価',
    'col_author' => '投稿者',
    'col_review' => 'レビュー',
    'only_rating' => '評価のみ',
    'col_reply' => '返信',
    'col_status' => 'ステータス',
    'col_replied_by' => '返信者',
    'col_date' => '日付',
    'replied_ai' => 'AI',
    'replied_human' => 'チーム',
    'replied_assistant' => 'アシスタント',
    'replied_api' => 'API',
    'replied_google' => 'Google',
    'no_reply' => '返信なし',
    'status_replied' => '返信済み',
    'status_pending' => '保留中',
    'status_scheduled' => '予約済み',
    'scheduled_for' => ':datetimeに投稿',
    'replied_at' => ':datetimeに返信',
    'status_failed' => '失敗',

    // Filters
    'review_date' => 'レビュー日',
    'filter_from' => ':dateから',
    'filter_to' => ':dateまで',
    'reply_status' => '返信ステータス',
    'review_text' => 'レビュー本文',
    'with_text' => 'テキストあり',
    'rating_only' => '評価のみ',
    'photos' => '写真',
    'with_photos' => '写真あり',
    'without_photos' => '写真なし',

    // Reply action
    'edit_reply' => '返信を編集',
    'save_reply' => '保存',
    'reply' => '返信',
    'reply_to_review' => 'レビューに返信',
    'no_written_review' => 'テキストのレビューはなく、評価のみです。',
    'translated_by_google' => '🌐 Googleによる翻訳',
    'ai_agent' => 'AIエージェント',
    'default_agent' => 'デフォルトエージェント',
    'your_reply' => 'あなたの返信',
    'generate_with_ai' => 'AIで生成',
    'generate' => '生成',
    'generating' => '返信を生成中…',
    'cancel' => 'キャンセル',
    'add_emoji' => '絵文字を追加',
    'show_translation' => '翻訳を表示（:language）',
    'translation_label' => '翻訳（:language）',
    'translation_failed' => '翻訳に失敗しました',
    'hide_emoji' => '絵文字を非表示',
    'delete_reply' => '返信を削除',
    'delete_reply_desc' => 'これにより返信がGoogleから削除されます。レビュー自体には影響しません。',
    'delete_confirm' => '削除',
    'submit_heading' => '返信を公開しますか？',
    'submit_desc' => 'これにより返信がGoogle上で公開され、レビューを見るすべての人に表示されます。',
    'submit_confirm' => '公開',

    // AI cost hints
    'cost_generic' => 'これはAIで返信を生成します。',
    'cost_all_used' => '今月のAI返信をすべて使い切りました。パックをチャージするか、アップグレードするか、手動で返信してください。',
    'cost_credit' => 'これはクレジットを1つ使用します（残り:count）。',
    'cost_monthly' => 'これは月間AI返信のうち1つを使用します（残り:count）。',

    // Notifications
    'reply_deleted' => '返信を削除しました',
    'no_changes' => '保存する変更はありません',
    'reply_published' => '返信を公開しました',
    'reply_failed' => '返信を投稿できませんでした',
    'ai_limit_reached' => 'AI上限に達しました',
    'ai_limit_body' => '今月のAI返信をすべて使い切りました。手動で編集するか、より高い上限にアップグレードしてください。',
    'generation_failed' => '生成に失敗しました',
    'reply_generated' => '返信を生成しました',
    'retry' => '再試行',
    'retry_heading' => 'この返信を再試行しますか？',
    'retry_desc' => 'もう一度試みます。下書きした返信を再投稿するか、AIのステップで失敗した場合は再生成します。',
    'retry_queued' => '返信を再度キューに追加しました',
    'retry_nothing' => '再試行するものがありません。代わりに手動で返信してください。',

    // Status tabs (mirror the auto-reply approval queue)
    'tab_all' => 'すべて',
    'tab_needs_approval' => '承認が必要',
    'tab_scheduled' => '予約済み',
    'tab_published' => '公開済み',
    'tab_failed' => '失敗',

    // Deep-link banner from the new-reviews digest email
    'from_email' => '{1} メール通知から1件のレビューを表示しています|[2,*] メール通知から:count件のレビューを表示しています',
    'from_email_clear' => 'すべてのレビューを表示',
];
