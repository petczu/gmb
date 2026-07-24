<?php

declare(strict_types=1);

return [
    'nav' => '競合',
    'title' => '競合',
    'intro' => '近隣のビジネスを追跡し、そのGoogle評価とレビュー数をあなたの店舗と比較します。数値は毎日自動的に更新されます。',

    'empty' => '競合はまだありません。',
    'empty_desc' => '競合を追加して、そのGoogle評価とレビューの増加を追跡しましょう。',

    'not_configured_title' => '競合追跡が設定されていません',
    'not_configured_body' => '競合ベンチマークを有効にするには、サーバー環境にGOOGLE_PLACES_API_KEY（Google Places APIキー）を設定してください。',

    'col_battle' => '競合',
    'col_name' => '競合',
    'col_rating' => '評価',
    'col_reviews' => 'レビュー',
    'filter_location' => '店舗',
    'filter_city' => '市区町村',
    'col_vs' => '自店舗との比較',
    'col_location' => '自店舗側',
    'col_checked' => '更新',

    'untitled_battle' => '無題の比較',
    'default_battle_name' => '{1} :location 対 競合1社|[2,*] :location 対 競合:count社',
    'own_locations_count' => ':count店舗',
    'rating_weighted_hint' => '競合全体で平均した評価で、各社のレビュー数で加重しています。',

    'vs_ahead' => ':delta ★リード',
    'vs_behind' => ':delta ★ビハインド',
    'vs_tied' => '同点',
    'vs_unknown' => '-',

    'add' => '競合を追加',
    'add_heading' => '競合を追加',
    'edit' => '編集',
    'edit_heading' => '競合を編集',
    'field_name' => '比較の名前',
    'field_name_placeholder' => '例: メインストリート 対 近隣',
    'field_your_locations' => 'あなたの店舗',
    'field_your_locations_helper' => '自店舗側として、1つ以上の店舗を選択してください。',
    'field_place' => '競合',
    'field_places' => '競合',
    'field_places_helper' => 'ビジネス名（と市区町村）を入力してGoogle Placesを検索します。',
    'already_tracked' => 'この競合はすでに追跡しています。',
    'saved' => '競合を保存しました',
    'some_failed' => ':count社の競合を取得できず、スキップされました。',

    'duplicate' => '複製',
    'duplicate_heading' => '競合を複製',
    'copy_name' => ':name（コピー）',
    'remove' => '削除',
    'removed' => '競合を削除しました',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'グループを作成',
    'group_heading' => '競合をグループ化',
    'group_need_two' => 'グループ化するには、少なくとも2社の競合を選択してください。',
    'group_created' => 'グループを作成しました',
    'group_removed' => 'グループを削除しました',
    'ungroup' => 'グループから削除',
    'ungrouped' => 'グループから削除しました',
    'field_group_name' => 'グループ名',
    'field_group_competitors' => '競合',
    'field_group_competitors_helper' => 'これらの競合は増加チャート上で1本の線にまとまり、レビューが合算されます。',
    'col_group' => 'グループ',

    'col_new_reviews' => '新しいレビュー',
    'col_rating_trend' => '評価の変化',
    'col_trend' => 'トレンド',
    'you_delta' => '自店舗: :delta',
    'trend_hint' => '選択した期間の新しいレビュー。',
    'trend_collecting' => '収集中…',
    'period_4w' => '4週間',
    'period_12w' => '3か月',

    'collecting' => '収集中…',
    'prev_delta' => '前回: :delta',
    'period_7d' => '7日間',
    'period_6m' => '6か月',
    'no_change' => '変化なし',
    'search_failed' => '競合検索は一時的にご利用いただけません',

    // Competitor detail modal
    'view' => '詳細を表示',
    'close' => '閉じる',
    'you' => '自店舗',
    'reviews_count' => '{1} 1件のレビュー|[2,*] :count件のレビュー',
    'no_distribution' => '星評価の内訳はまだ利用できません（次回の更新時に反映されます）。',
];
