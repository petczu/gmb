<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'まだ自動化がありません',
    'empty_desc' => '自動化を設定して、評価と店舗ごとに新しいレビューへ自動的に返信しましょう。',
    'empty_cta' => '新しい自動化',

    // Table columns
    'col_rating' => '評価',
    'rating_any' => 'すべて',
    'col_reply' => '返信',
    'reply_ai' => 'AI: :agent',
    'reply_default' => 'デフォルトメッセージ',
    'col_mode' => 'モード',
    'mode_approval' => '承認',
    'mode_auto' => '自動公開',
    'col_scope' => '対象範囲',
    'scope_all' => 'すべての店舗',
    'scope_count' => ':count店舗',

    // Run action
    'run_now' => '今すぐ実行',
    'run_heading' => 'この自動化を今すぐ実行',
    'run_desc' => 'この自動化を条件に合う未返信のレビューに適用します。任意でレビュー日の期間で絞り込めます。すべてを含めるには両方のフィールドを空欄のままにしてください。',
    'run_from' => 'レビュー開始日',
    'run_until' => 'レビュー終了日',
    'run_title' => '「:name」を実行しました',
    'run_queued_title' => '「:name」をキューに追加しました',
    'run_queued_body' => '実行はバックグラウンドで行われます。新しい下書きは承認に届き、自動公開された返信は数分かけてレビューに表示されます。',
    'run_body' => '生成:generated件、公開:published件、キュー追加:queued件、スキップ:skipped件。',

    // Form — Flow section
    'flow_section' => 'フロー',
    'flow_section_desc' => '自動化がいつ実行され、どのレビューに適用されるか。',
    'trigger' => 'トリガー',
    'trigger_new_review' => 'Googleの新しいレビュー',
    'rating_is' => '評価が…',
    'rating_helper' => 'すべての評価に適用するには、すべてのチェックを外したままにしてください。',
    'all_locations' => 'すべての店舗',
    'locations' => '店舗',
    'all_locations_helper' => '包括的な設定として機能します。特定の店舗に限定された自動化が、その店舗については優先されます。',
    'covered_by' => 'すでに「:name」に含まれています（:ratings）',
    'any_rating' => 'すべての評価',
    'overlap_title' => '別の自動化と重複しています',
    'overlap_body' => '同じレビューにも一致します: :list。各レビューはちょうど1つの自動化で処理されます。特定の店舗が「すべての店舗」より優先され、それ以外の場合は古い方が実行されます。',
    'respect_working_hours' => '営業時間を尊重',
    'respect_working_hours_helper' => '店舗の営業時間内にのみ返信します。',
    'reply_to_previous' => '過去のレビューに返信',
    'reply_to_previous_helper' => '既存の未返信レビューも処理します（月間AI割当に計上されます）。',
    'approve_before_posting' => '投稿前に承認',
    'approve_before_posting_helper' => 'オフ = Googleに自動公開。オン = まず承認に送信。',

    // Form — Timing section
    'timing_section' => 'タイミング',
    'timing_section_desc' => 'ランダムな遅延（および任意の営業時間）を追加し、返信がすぐにではなく、人間のペースで自然な時刻に投稿されるようにします。',
    'reply_delay_min' => '最小遅延',
    'reply_delay_max' => '最大遅延',
    'minutes_suffix' => '分',
    'reply_delay_helper' => '返信は最小値と最大値の間のランダムな遅延の後に投稿され、自然に見えます。すぐに投稿するには両方を0に設定してください。',
    'reply_delay_max_error' => '最大遅延は最小遅延以上である必要があります。',
    'working_days' => '営業日',
    'working_start' => '開始時刻',
    'working_end' => '終了時刻',
    'day_mon' => '月',
    'day_tue' => '火',
    'day_wed' => '水',
    'day_thu' => '木',
    'day_fri' => '金',
    'day_sat' => '土',
    'day_sun' => '日',

    // Form — Content section
    'content_section' => 'コンテンツ',
    'content_section_desc' => 'どの返信を送信するか。',
    'content_ai_agent' => 'AIエージェント',
    'content_default_message' => 'デフォルトメッセージ',
    'ai_agent' => 'AIエージェント',
    'default_message' => 'デフォルトメッセージ',
];
