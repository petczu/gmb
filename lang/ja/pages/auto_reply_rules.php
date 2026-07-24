<?php

declare(strict_types=1);

return [
    'title' => 'AI自動返信ルール',
    'section' => ':stars  ·  :rating星のレビュー',
    'enabled' => '自動返信を有効化',
    'mode' => 'モード',
    'mode_auto' => '自動公開',
    'mode_draft' => '承認用の下書き',
    'tone' => 'トーン / テンプレート',
    'tone_placeholder_positive' => '例: 温かく感謝を込めて。',
    'tone_placeholder_negative' => '例: お詫びし、改善を申し出る。',
    'instruction' => '追加の指示（任意）',
    'language' => '言語',
    'language_placeholder' => 'レビューから自動検出',
    'save_rules' => 'ルールを保存',
    'rules_saved' => '自動返信ルールを保存しました',

    // Blade intro
    'intro' => '各星評価に対してAIがどのように返信するかを設定します。<strong>自動公開</strong>は返信をすぐにGoogleに投稿します。<strong>承認用の下書き</strong>はまず承認キューに送ります。各生成はプランの月間AI割当に計上されます。',
];
