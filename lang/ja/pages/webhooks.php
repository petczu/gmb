<?php

declare(strict_types=1);

return [
    'pro_only_title' => 'WebhookはPro機能です',
    'pro_only_body' => 'レビューが届いた瞬間、返信が公開された瞬間、目標が達成された瞬間、または異常が検出された瞬間に、署名付きのHTTP POSTを受け取れます。エンドポイントを追加するにはProにアップグレードしてください。',
    'see_plans' => 'プランを見る',

    'intro' => '購読している各イベントで、署名付きのJSONペイロードをあなたのエンドポイントにPOSTします（再試行あり）。X-Webhook-Signatureヘッダーをエンドポイントのシークレットと照合して検証してください。',

    'docs_link' => 'Webhookドキュメント',
    'empty' => 'Webhookエンドポイントはまだありません。',
    'col_url' => 'URL',
    'col_events' => 'イベント',
    'col_active' => '有効',
    'col_last' => '最終発火',

    'create' => 'エンドポイントを追加',
    'create_heading' => 'Webhookエンドポイントを追加',
    'edit' => '編集',
    'delete' => '削除',
    'saved' => 'エンドポイントを保存しました',
    'created' => 'エンドポイントを追加しました',
    'deleted' => 'エンドポイントを削除しました',

    'field_name' => '名前（任意）',
    'field_url' => 'エンドポイントURL',
    'field_events' => 'イベント',
    'field_active' => '有効',

    'secret' => 'シークレット',
    'secret_heading' => '署名シークレット',
    'secret_desc' => 'ペイロードの署名を検証するために使用します。',
    'signature_hint' => '各リクエストは署名されます:',

    'deliveries' => '配信',
    'deliveries_heading' => '最近の配信',
    'no_deliveries' => '配信はまだありません。',
    'attempts' => '回の試行',
    'resend' => '再送',
    'resent' => '配信を再度キューに追加しました',
    'status_pending' => '保留中',
    'status_success' => '配信済み',
    'status_failed' => '失敗',

    'event_review_created' => '新しいレビュー',
    'event_reply_published' => '返信が公開された',
    'event_goal_reached' => '目標達成',
    'event_anomaly_detected' => '異常を検出',
];
