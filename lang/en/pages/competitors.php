<?php

declare(strict_types=1);

return [
    'nav' => 'Competitors',
    'title' => 'Competitor benchmark',
    'intro' => 'Track nearby businesses and compare their Google rating and review count with your locations. Numbers refresh automatically every day.',

    'empty' => 'No competitions yet.',
    'empty_desc' => 'Create a competition to compare your locations against a group of competitors.',

    'not_configured_title' => 'Competitor tracking is not configured',
    'not_configured_body' => 'Set GOOGLE_PLACES_API_KEY in the server environment (a Google Places API key) to enable competitor benchmarking.',

    'col_battle' => 'Competition',
    'col_name' => 'Competitor',
    'col_rating' => 'Rating',
    'col_reviews' => 'Reviews',
    'col_vs' => 'Vs. you',
    'col_location' => 'Your side',
    'col_checked' => 'Updated',

    'untitled_battle' => 'Untitled competition',
    'default_battle_name' => '{1} :location vs 1 competitor|[2,*] :location vs :count competitors',
    'own_locations_count' => ':count locations',
    'rating_weighted_hint' => 'Rating averaged across the competitors, weighted by their review counts.',

    'vs_ahead' => 'You lead by :delta ★',
    'vs_behind' => 'They lead by :delta ★',
    'vs_tied' => 'Tied',
    'vs_unknown' => '—',

    'add' => 'Create',
    'add_heading' => 'Create new competition',
    'edit' => 'Edit',
    'edit_heading' => 'Edit competition',
    'field_name' => 'Competition name',
    'field_name_placeholder' => 'e.g. Main Street vs the neighborhood',
    'field_your_locations' => 'Your locations',
    'field_your_locations_helper' => 'Pick one or more of your locations for your side.',
    'field_places' => 'Competitors',
    'field_places_helper' => 'Type a business name (and city) to search Google Places. Add as many as you like.',
    'saved' => 'Competition saved',
    'some_failed' => ':count competitor(s) could not be fetched and were skipped.',

    'remove' => 'Remove',
    'removed' => 'Competition removed',

    'col_new_reviews' => 'New reviews',
    'col_rating_trend' => 'Rating change',
    'col_trend' => 'Trend',
    'you_delta' => 'you: :delta',
    'trend_hint' => 'New reviews in the selected period. Green when you grow at least as fast.',
    'trend_collecting' => 'collecting…',
    'period_4w' => '4 weeks',
    'period_12w' => '3 months',

    'collecting' => 'collecting…',
    'prev_delta' => 'prev: :delta',
    'period_7d' => '7 days',
    'period_6m' => '6 months',
    'no_change' => 'no change',
    'search_failed' => 'Competitor search is temporarily unavailable',
];
