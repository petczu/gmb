<?php

declare(strict_types=1);

return [
    'performance_report' => 'Performance report',
    'generated' => 'Generated :date',
    'reviews_received' => 'Reviews received',
    'average_rating' => 'Average rating',
    'response_rate' => 'Response rate',
    'replies_sent' => 'Replies sent',
    'vs_prev' => 'vs prev',
    'executive_summary' => 'Executive summary',
    'recommendations' => 'Recommendations',
    'star_distribution' => 'Star distribution',
    'reviews_per_day' => 'Reviews per day',
    'reviews_per_week' => 'Reviews per week',
    'staff_mentions' => 'Staff mentions',
    'name' => 'Name',
    'mentions' => 'Mentions',
    'sentiment' => 'Sentiment',
    'positive' => 'Positive',
    'mixed' => 'Mixed',
    'negative' => 'Negative',
    'highlights_positive' => 'Highlights, positive',
    'highlights_attention' => 'Highlights, needs attention',
    'no_positive' => 'No positive reviews with text in this period.',
    'no_critical' => 'No critical reviews with text in this period. 🎉',
    'anonymous' => 'Anonymous',
    'footer_positive' => ':pct% positive',
    'footer_critical' => ':pct% critical',
    'footer_compared' => 'compared to :period',
    'footer_busiest' => 'busiest day :day (:count reviews)',

    // At a glance
    'five_star_share' => '5-star share',
    'out_of_5' => 'of 5.00 stars',
    'of' => 'of',

    // Staff mentions
    'staff_intro' => 'Reviews were checked for named team members. The table counts the reviews crediting each person, useful for bonus allocation.',
    'share_credits' => 'Share of total',
    'notes' => 'Notes',
    'total_credits' => 'Total',

    // Collection cadence
    'cadence_title' => 'Collection cadence, are reviews spread out?',
    'cadence_intro' => 'Reviews arrived on :active of :total days, averaging :avg per active day. Reviews bunched into the same session look artificial to Google and risk being filtered.',
    'legend' => 'Legend',
    'legend_low' => 'green = 1-2',
    'legend_mid' => 'amber = 3-4',
    'legend_high' => 'red = 5+ (clustering)',
    'day' => 'Day',
    'time_window' => 'Time window',
    'volume' => 'Volume',
    'flag' => 'Flag',
    'flag_high' => 'High',
    'flag_medium' => 'Medium',
    'reviews_lc' => 'reviews',
    'why_matters' => 'Why this matters',
    'cadence_why' => "Google's spam systems look for reviews that appear in tight batches from the same device or location, a portion of the batch can be silently withheld from the public rating. The goal is to space collection out so all of it survives.",
    'cadence_clean' => 'No tight same-session bursts detected, collection looks naturally spread out. 🎉',

    // What customers talk about (top topics)
    'topics_title' => 'What customers talk about',

    // Themes & sentiment
    'themes_title' => 'Themes & sentiment',
    'praised' => 'What guests praised',
    'complaints' => 'What guests complained about',

    // Response performance
    'responses_title' => 'Response performance',
    'reply_rate' => 'Reply rate',
    'unanswered' => 'Unanswered',
    'avg_response' => 'Avg. response time',
    'to_reply' => 'to reply',
    'within_24h' => 'Within 24h',
    'of_replies' => 'of replies',

    // Methodology
    'methodology' => 'Methodology & notes',
    'method_scope' => 'Scope: all Google reviews for :business with a creation date in :period (:count reviews).',
    'method_ratings' => 'Ratings: 5★ × :five, 4★ × :four, 3★ × :three, 2★ × :two, 1★ × :one.',
    'method_cadence' => 'Cadence is based on the review creation timestamp, which is the signal Google evaluates.',
    'method_source' => 'Source: Google Business Profile.',

    'competitors_title' => 'Competitor benchmark',
    'competitors_col_business' => 'Business',
    'competitors_col_rating' => 'Rating',
    'competitors_col_reviews' => 'Reviews',
    'competitors_col_new' => 'New in period',
    'competitors_you' => 'you',
    'competitors_note' => 'Competitor data from Google. \"New in period\" is based on daily snapshots and appears once enough history is collected.',
];
