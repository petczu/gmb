<?php

declare(strict_types=1);

return [
    // Sample report preview (no locations connected yet)
    'demo_business' => 'デモレストラン',
    'demo_period' => 'パフォーマンスレポート · 過去30日間',
    'demo_five_star' => '5つ星の割合',
    'demo_summary_label' => 'エグゼクティブサマリー',
    'demo_summary' => 'デモレストランは過去30日間に38件のレビューを受け取り（前期間比+9）、平均4.60★でした。レビューの84%がポジティブで、返信率は92%に達しました。お客様は親しみやすいチームと迅速なサービスを繰り返し称賛しました。',

    'location' => '店舗',
    'business_multi' => ':name 他:count件',
    'compare' => '比較',
    'compare_options' => [
        'none' => '比較しない',
        'previous' => '前期間',
        'custom' => 'カスタム期間…',
    ],
    'compare_from' => '比較開始',
    'compare_to' => '比較終了',
    'report_language' => 'レポートの言語',

    'content_section' => 'レポートの内容',
    'content_section_desc' => 'プリセットを選択してから、レポートに表示するブロックを細かく調整します。',
    'preset' => 'プリセット',
    'blocks' => 'ブロック',
    'competitors_block_hint' => '追跡中の競合はまだありません。まずリスティング > 競合で追加してください。',
    'ai_instructions' => 'AIへの指示',
    'ai_instructions_help' => 'AIの文章に対する任意のガイダンスです。スタッフ名に最も役立ちます。チームメンバーとあだ名を挙げておくと、言及が正しい人物に結び付けられます。一度保存すると、スケジュールされたものを含む今後のすべてのレポートに適用されます。',
    'ai_instructions_placeholder' => '当店のスタッフ: Eva、Alette、Suleyman（Sulyとも表記）、Lisa。あだ名はフルネームにまとめてください。',
    'ai_improve' => 'AIで改善',
    'ai_improve_empty' => 'まずメモを書いてから、改善してください。',
    'ai_improve_rate_limited' => '試行回数が多すぎます。後でもう一度お試しください。',
    'ai_improve_done' => '指示を改善しました',
    'ai_improve_failed' => '指示を改善できませんでした。再度お試しください。',

    'schedule_report' => 'スケジュールで送信',
    'schedule_heading' => 'このレポートをスケジュール',
    'schedule_desc' => '現在の選択（期間、店舗、比較、ブロック）が、定期的なスケジュールでPDFとしてメール送信されます。',
    'schedule_submit' => 'スケジュールを作成',
    'schedule_created' => 'スケジュールを作成しました',
    'schedule_created_body' => 'レポート → スケジュールされたレポートで管理できます。',

    // Usage line ("N of M AI reports left this month")
    'usage' => '今月のAIレポートは:cap件中:left件残っています',

    // Generate modal
    'generate_heading' => 'AIレポートを生成しますか？',
    'generate_desc' => '現在の選択に対してAIエグゼクティブサマリーを生成します。',
    'generate_desc_left' => 'これは月間AIレポートのうち1件を使用します（残り:left件）。',
    'generate_submit' => '生成',

    // Generate notifications
    'report_generated' => 'レポートを生成しました',
    'report_generated_body' => 'AI要約の準備ができ、プレビューが更新されました。ダウンロードでPDFを保存してください。',
    'limit_reached' => '月間レポート上限に達しました',
    'limit_reached_body' => 'AIなしの基本レポートを表示しています。より高い月間上限にはアップグレードしてください。',

    // Blade view
    'generate_report' => 'レポートを生成',
    'generating' => '生成中…',
    'download_pdf' => 'PDFをダウンロード',
    'download_first_tooltip' => 'まずレポートを生成してください',
    'building' => 'レポートを作成中…',
    'preview_title' => 'レポートプレビュー',
];
