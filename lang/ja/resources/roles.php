<?php

declare(strict_types=1);

return [
    'empty_heading' => 'まだ役割がありません',
    'empty_desc' => '役割を作成して、各チームメンバーが閲覧・操作できる内容を管理しましょう。',
    'empty_cta' => '新しい役割',
    'col_permissions' => '権限',
    'col_members' => 'メンバー',
    'new_role' => '新しい役割',
    'pro_locked' => 'カスタム役割はPro機能です',
    'pro_locked_body' => 'Proプランにアップグレードすると、カスタム権限を持つ独自の役割を作成できます。',

    // Form
    'section' => '役割',
    'name_helper' => '例: owner、admin、editor。小文字、スペースなし。',
    'permissions_helper' => 'この役割がワークスペースで行えること。',

    'permissions_section' => '権限',

    'group_reviews' => 'レビュー',
    'group_locations' => '店舗',
    'group_publishing' => '公開',
    'group_automations' => '自動化',
    'group_reports' => 'レポート',
    'group_team' => 'チームと設定',
    'group_billing' => 'お支払い',

    'perm_view_reviews' => 'レビューを閲覧',
    'perm_view_reviews_desc' => 'レビュー受信トレイを開き、AIに質問を使用',
    'perm_manage_reviews' => 'レビューに返信',
    'perm_manage_reviews_desc' => '返信の作成と編集、AIの下書きの承認',
    'perm_delete_replies' => '返信を削除',
    'perm_delete_replies_desc' => '公開済みの返信をGoogleから削除',
    'perm_manage_review_pages' => 'レビュー収集ページ',
    'perm_manage_review_pages_desc' => '公開QRレビューページを設定',

    'perm_manage_locations' => '店舗を管理',
    'perm_manage_locations_desc' => 'Google店舗の連携と連携解除',
    'perm_edit_business_info' => 'ビジネス情報を編集',
    'perm_edit_business_info_desc' => 'Google上の営業時間、説明、連絡先を変更',

    'perm_publish_posts' => '投稿を公開',
    'perm_publish_posts_desc' => 'Google投稿を作成（更新、特典、イベント）',

    'perm_manage_automations' => '自動化を管理',
    'perm_manage_automations_desc' => '自動返信の自動化を作成・実行',
    'perm_manage_ai_agents' => 'AIエージェントを管理',
    'perm_manage_ai_agents_desc' => 'AI返信のペルソナと知識を編集',

    'perm_view_reports' => 'レポートを閲覧',
    'perm_view_reports_desc' => 'ダッシュボードとレポートビルダーを開く',
    'perm_generate_reports' => 'AIレポートを生成',
    'perm_generate_reports_desc' => 'AIレポート生成を実行（月間割当を使用）',
    'perm_manage_reports' => 'スケジュールを管理',
    'perm_manage_reports_desc' => 'スケジュールされたレポートメールを作成・編集',

    'perm_manage_team' => 'チームを管理',
    'perm_manage_team_desc' => 'メンバーの招待、削除、アクティビティの閲覧',
    'perm_manage_roles' => '役割を管理',
    'perm_manage_roles_desc' => '役割の作成と権限の変更',
    'perm_manage_notifications' => '通知設定',
    'perm_manage_notifications_desc' => '振り分け、アラート、Slack/Telegramチャンネル',
    'perm_manage_integrations' => '連携',
    'perm_manage_integrations_desc' => 'APIキー、Webhook、MCPアクセス',

    'perm_manage_billing' => 'お支払いを管理',
    'perm_manage_billing_desc' => 'サブスクリプション、クレジット、請求書',

    'perm_view_competitors' => '競合',
    'perm_view_competitors_desc' => '競合ベンチマークを開き、競合を追加',
    'perm_manage_company' => '会社設定',
    'perm_manage_company_desc' => '会社プロフィール、ブランディング、業種を編集',
];
