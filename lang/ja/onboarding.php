<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => '会社情報を追加',
    'step_company_hint' => '請求書やレポートに使用する国と請求情報です。',
    'step_plan_label' => 'プランを選択',
    'step_plan_hint' => '14日間の無料トライアルを開始します。カードは不要です。',
    'step_location_label' => '最初の店舗を連携',
    'step_location_hint' => 'Googleビジネスプロフィールを連携してレビューの取り込みを開始します。',

    // Setup wizard (/onboarding)
    'wizard_title' => 'ワークスペースをセットアップ',
    'wiz_plan_done' => '✓ プランが有効になりました。次のステップに進んでください。',
    'wiz_plan_pick' => 'プランを選択',
    'wiz_interval' => '請求間隔',
    'wiz_monthly' => '月額',
    'wiz_yearly' => '年額',
    'wiz_start_trial' => '14日間の無料トライアルを開始',
    'wiz_trial_note' => '続行するとすぐに14日間の無料トライアルが始まります。カードは不要です。',
    'wiz_go_checkout' => 'チェックアウトに進む',
    'wiz_plan_required' => '続行するにはプランを選択し、チェックアウトを完了してください。',
    'wiz_location_body' => 'Googleビジネスプロフィールを連携すると、レビューを取り込めます。アクセスを承認するためGoogleにリダイレクトされた後、連携する店舗を選択してください。',
    'wiz_connect_google' => 'Googleビジネスプロフィールを連携',
    'wiz_skip_location' => '今はスキップ',
    'skipped_title' => '準備完了です',
    'skipped_body' => 'Googleビジネスプロフィールは、店舗ページからいつでも連携できます。',
    'wiz_per_location' => '店舗あたり / 月',
    'wiz_plan_desc_starter' => 'レビュー受信トレイ、手動返信、基本レポート。',
    'wiz_plan_desc_growth' => 'AI自動返信、スケジュールレポート、比較機能を追加。',
    'wiz_plan_desc_pro' => 'すべての機能に加え、ホワイトラベル、API、MCP、クライアントアクセス。',

    // Onboarding overlay
    'welcome_title' => 'ようこそ。アカウントをセットアップしましょう',
    'welcome_subtitle' => 'いくつかの簡単なステップで準備が整います。',
    'continue_step' => '続行: :label',
    'enter_app' => 'アプリを開く →',
    'sign_out' => 'サインアウト',

    // Pending-deletion overlay
    'deletion_title' => 'このワークスペースは削除予定です',
    'deletion_body' => 'すべてのデータは<strong>:date</strong>に完全に削除されます。今ならキャンセルしてワークスペースを維持できます。',
    'cancel_deletion' => '削除をキャンセル',

    // Grace banner
    'grace_banner' => '⚠️ 前回のお支払いを処理できませんでした。サービスは<strong>:date</strong>まで有効です。お手数ですが',
    'update_your_billing' => 'お支払い情報を更新してください',

    // Paywall overlay
    'payment_problem_title' => 'お支払いに問題があります',
    'needs_plan_title' => 'プランを選択して始めましょう',
    'payment_problem_body' => 'お支払いを処理できなかったため、アクセスが一時停止されています。続行するにはお支払い情報を更新してください。',
    'needs_plan_body' => 'プランを選択して、店舗のレビュー、AI返信、レポートを有効にしましょう。14日間の無料トライアルです。',
    'update_billing' => 'お支払い情報を更新',
    'view_plans' => 'プランを見る',

    // Connect-select-location page
    'connecting_location' => '店舗を連携中…',
    'choose_location' => 'このワークスペースに連携するGoogleビジネス店舗を選択してください。',
    'could_not_load' => '店舗を読み込めませんでした',
    'pending_expired_title' => 'Googleセッションの有効期限が切れました',
    'pending_expired' => 'Googleの承認は短時間のみ有効で、今回のものは期限切れです。再連携して店舗を選び直してください。すぐに完了します。',
    'reconnect_google' => 'Googleを再連携',
    'back' => '戻る',
    'no_locations_available' => '利用できる店舗がありません',
    'no_locations_body' => 'Googleビジネス店舗が返されませんでした。Google側でまだ読み込み中の可能性があります。しばらくして再度お試しください。',
    'connect_then_done' => '1つ以上の店舗を連携してから、完了をクリックしてください。',
    'done' => '完了',
    'connected' => '連携済み',
    'connect' => '連携',
    'connecting' => '連携中…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'ビジネス店舗を選択',
    'connect_failed' => '店舗を連携できませんでした',
    'connected_title' => '連携しました: :name',
    'connected_body' => 'レビューはバックグラウンドで同期中です。まもなく店舗ページに表示されます。',
    'location_fallback' => '店舗',
    'trial_started_title' => '14日間のトライアルが始まりました',
    'trial_started_body' => ':dateまでフルアクセスできます。カードは不要です。お楽しみください！',
];
