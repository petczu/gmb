<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'まだAIエージェントがありません',
    'empty_desc' => 'AIエージェントを作成して、あなたのブランドの言葉づかいで返信を下書きし、自動返信の自動化を動かしましょう。',
    'empty_cta' => '新しいAIエージェント',

    // Table
    'col_name' => '名前',
    'col_tone' => 'トーン',
    'name' => '名前',
    'col_native_lang' => '母国語',
    'col_default' => 'デフォルト',
    'col_updated' => '更新日',
    'test_preview' => 'テストとプレビュー',
    'test_heading' => '返信をテスト',
    'close' => '閉じる',
    'no_reviews_to_test' => 'テストできるレビューがまだありません。まずレビューを同期してください。',
    'generation_failed' => '生成に失敗しました: :error',
    'set_default' => 'デフォルトに設定',

    // Form
    'section' => 'あなたのAIエージェント',
    'section_desc' => 'エージェントに名前を付け、どのように返信すべきかを記述してください。自動返信の自動化と「AIで下書き」ボタンで使用されます。',
    'describe' => 'エージェントを記述',
    'describe_helper' => '完全な指示 / ペルソナです。レビューの分類方法、返信方法、トーンとスタイル、パーソナライズのルールなど。',
    'tone' => '口調',
    'reply_native' => 'レビューの言語で返信',
    'reply_native_helper' => 'エージェントはレビューと同じ言語で返信します。',
    'default_agent' => 'デフォルトエージェント',
    'default_agent_helper' => '自動化がエージェントを指定していない場合に使用されます。',

    // Knowledge base
    'knowledge' => 'ナレッジベース（任意）',
    'knowledge_helper' => 'エージェントが返信に使えるビジネスの事実です: 営業時間、ポリシー、部屋やサービスの名称、特典、FAQなど。事実に基づいて扱われ、これ以外のことは決して創作されません。',
    'knowledge_ph' => '例: 営業 月〜日 10:00〜22:00。部屋: The Heist、Prison Break、Haunted Manor。2〜6名のグループ。予約は example.com または +43 ...',

    // Test panel
    'test_section' => 'レビューでテスト',
    'test_section_desc' => '実際のレビューを選び、現在の（未保存の）設定で下書きを生成してから調整します。',
    'test_pick_review' => 'レビュー',
    'test_pick_placeholder' => '同期済みのレビューを選択…',
    'test_review_text' => 'レビュー',
    'test_generate' => '下書きを生成',
    'test_result' => '生成された下書き',
    'test_need_review' => 'まずテストするレビューを選択してください。',

    // AI description generator
    'generate_label' => 'AIで生成',
    'generate_heading' => 'AIで説明を生成',
    'generate_desc' => 'ウェブサイトやビジネスについての簡単な説明を追加すると、AIがエージェントの指示を下書きします。結果は後から編集できます。',
    'generate_submit' => '生成',
    'generate_url' => 'ウェブサイトURL',
    'generate_notes' => '追加事項（任意）',
    'generate_notes_ph' => '例: 家族経営のイタリアンレストラン、親しみやすい接客が中心、夏はテラスに触れる',
    'generate_need_input' => 'まずウェブサイトURLか短い説明を追加してください。',
    'generate_rate_limited' => '生成が多すぎます。少し待ってから再度お試しください。',
    'generate_done' => '説明を生成しました。必要に応じて確認・調整してください。',
    'generate_failed' => '説明を生成できませんでした。再度お試しいただくか、手動で記述してください。',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => '共通ルール',
    'shared_rules_heading' => '共通の返信ルール',
    'shared_rules_desc' => 'これらのルールは、すべてのAI返信において、すべてのエージェントに上乗せして適用されます。エージェントごとに繰り返したくないスタイルの修正に最適です。',
    'shared_rules_placeholder' => "例:\nドイツ語の返信では「Raum」または「Escape Room」を使い、ドイツ語の名詞として「Room」は決して使わない。\n割引や返金を約束しない。\n返信に名前を署名しない。",
    'shared_rules_save' => 'ルールを保存',
    'shared_rules_saved' => '共通ルールを保存しました',
];
