<?php

declare(strict_types=1);

return [
    'nav' => 'Competitors',
    'title' => 'Competitor benchmark',
    'intro' => 'Track nearby businesses and compare their Google rating and review count with your locations. Numbers refresh automatically every day.',

    'empty' => 'No competitors yet.',
    'empty_desc' => 'Add the businesses you compete with to see how your rating stacks up.',

    'not_configured_title' => 'Competitor tracking is not configured',
    'not_configured_body' => 'Set GOOGLE_PLACES_API_KEY in the server environment (a Google Places API key) to enable competitor benchmarking.',

    'col_name' => 'Competitor',
    'col_rating' => 'Rating',
    'col_reviews' => 'Reviews',
    'col_vs' => 'Vs. you',
    'col_location' => 'Your location',
    'col_checked' => 'Updated',

    'vs_ahead' => 'You lead by :delta ★',
    'vs_behind' => 'They lead by :delta ★',
    'vs_tied' => 'Tied',
    'vs_unknown' => '—',

    'add' => 'Add competitor',
    'add_heading' => 'Add a competitor',
    'field_location' => 'Compare against',
    'field_place' => 'Business',
    'field_place_helper' => 'Type a business name (and city) to search Google Places.',
    'added' => 'Competitor added',
    'add_failed' => 'Could not fetch the business',

    'remove' => 'Remove',
    'removed' => 'Competitor removed',

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
];
