<?php

declare(strict_types=1);

return [
    'nav' => 'Competitors',
    'title' => 'Competitors',
    'intro' => 'Track nearby businesses and compare their Google rating and review count with your locations. Numbers refresh automatically every day.',

    'empty' => 'No competitors yet.',
    'empty_desc' => 'Add a competitor to track their Google rating and review growth.',

    'not_configured_title' => 'Competitor tracking is not configured',
    'not_configured_body' => 'Set GOOGLE_PLACES_API_KEY in the server environment (a Google Places API key) to enable competitor benchmarking.',

    'col_battle' => 'Competitor',
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

    'add' => 'Add competitor',
    'add_heading' => 'Add competitor',
    'edit' => 'Edit',
    'edit_heading' => 'Edit competitors',
    'field_name' => 'Competition name',
    'field_name_placeholder' => 'e.g. Main Street vs the neighborhood',
    'field_your_locations' => 'Your locations',
    'field_your_locations_helper' => 'Pick one or more of your locations for your side.',
    'field_place' => 'Competitor',
    'field_places' => 'Competitors',
    'field_places_helper' => 'Type a business name (and city) to search Google Places.',
    'field_own_locations' => 'Your locations to compare against',
    'field_own_locations_helper' => 'Pick the locations (cities) this competitor competes with. It then shows only when those locations are selected on the dashboard and in reports.',
    'edit_locations' => 'Edit locations',
    'edit_locations_heading' => 'Which of your locations does this compete with?',
    'locations_updated' => 'Competitor locations updated',
    'already_tracked' => 'You already track this competitor.',
    'saved' => 'Competitor saved',
    'some_failed' => ':count competitor(s) could not be fetched and were skipped.',

    'duplicate' => 'Duplicate',
    'duplicate_heading' => 'Duplicate competitor',
    'copy_name' => ':name (copy)',
    'remove' => 'Remove',
    'removed' => 'Competitor removed',

    // Groups (competitor groups + your own location groups)
    'create_group' => 'Create group',
    'group_heading' => 'Group competitors',
    'group_need_two' => 'Pick at least two competitors to group.',
    'group_created' => 'Group created',
    'group_removed' => 'Group removed',
    'ungroup' => 'Remove from group',
    'ungrouped' => 'Removed from group',
    'field_group_name' => 'Group name',
    'field_group_competitors' => 'Competitors',
    'field_group_competitors_helper' => 'These competitors combine into one line on the growth chart, with their reviews summed.',
    'col_group' => 'Group',

    'col_new_reviews' => 'New reviews',
    'col_rating_trend' => 'Rating change',
    'col_trend' => 'Trend',
    'you_delta' => 'you: :delta',
    'trend_hint' => 'New reviews in the selected period.',
    'trend_collecting' => 'collecting…',
    'period_4w' => '4 weeks',
    'period_12w' => '3 months',

    'collecting' => 'collecting…',
    'prev_delta' => 'prev: :delta',
    'period_7d' => '7 days',
    'period_6m' => '6 months',
    'no_change' => 'no change',
    'search_failed' => 'Competitor search is temporarily unavailable',

    // Competitor detail modal
    'view' => 'View details',
    'close' => 'Close',
    'you' => 'You',
    'reviews_count' => '{1} 1 review|[2,*] :count reviews',
    'no_distribution' => 'Star breakdown not available yet (updates on the next refresh).',
];
