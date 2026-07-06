<?php

declare(strict_types=1);

return [
    'greeting' => 'Hi :name,',
    'signoff' => 'Thanks,',
    'team' => 'The Repunio team',

    'welcome' => [
        'subject' => 'Welcome to Repunio',
        'intro' => 'Your account is ready. Repunio helps you collect, reply to and report on your Google reviews, all in one place.',
        'next' => 'Next: connect your first location and pick a plan to start your 14-day free trial.',
        'cta' => 'Open Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Your free trial ends in :days days',
        'intro' => 'Your Repunio free trial ends on :date. Add a payment method now so nothing stops, your reviews keep syncing and AI replies keep working.',
        'note' => 'You will not be charged until the trial ends, and you can cancel anytime.',
        'cta' => 'Add payment method',
    ],

    'payment_succeeded' => [
        'subject' => 'Payment received',
        'intro' => 'We received your payment of :amount. Your Repunio subscription is active.',
        'cta' => 'View billing',
    ],

    'payment_failed' => [
        'subject' => 'Payment failed, action needed',
        'intro' => 'We could not process your last payment. Your account keeps working for :days days, please update your billing to avoid interruption.',
        'cta' => 'Update billing',
    ],

    'subscription_canceled' => [
        'subject' => 'Your subscription is set to cancel',
        'intro' => 'Your Repunio subscription has been canceled. You keep full access until :date, after which it will not renew.',
        'note' => 'Changed your mind? You can resume any time before then, no charge.',
        'cta' => 'Resume subscription',
    ],

    'subscription_resumed' => [
        'subject' => 'Your subscription is active again',
        'intro' => 'Your Repunio subscription has been resumed and will keep renewing as normal. Nothing else to do.',
        'cta' => 'View billing',
    ],

    'ai_limit' => [
        'subject' => 'You have used all your AI replies this month',
        'intro' => 'You have reached your monthly AI reply limit on the :plan plan. Upgrade for a higher limit, or keep replying manually until next month.',
        'cta' => 'See plans',
    ],

    'auto_recharge_failed' => [
        'subject' => 'AI top-up payment failed',
        'intro' => 'We tried to automatically top up your AI replies, but the payment did not go through. Please update your card so auto top-up can keep working.',
        'cta' => 'Update billing',
    ],

    'new_reviews' => [
        'subject' => ':count new review(s) for your business',
        'intro' => 'You have :count new review(s) for :location.',
        'col_author' => 'Author',
        'col_rating' => 'Rating',
        'col_location' => 'Location',
        'col_review' => 'Review',
        'cta' => 'View reviews',
    ],

    'account_disconnected' => [
        'subject' => 'Action needed: your Google connection stopped working',
        'intro' => 'The Google connection for ":account" stopped working, so your reviews are no longer syncing.',
        'detail' => 'Reconnect the account to resume syncing reviews and posting replies.',
        'cta' => 'Reconnect',
    ],

    'sync_restored' => [
        'subject' => 'Your Google connection is back',
        'intro' => 'Good news: the connection for ":account" is back and syncing has resumed. Your reviews are up to date again.',
        'cta' => 'Open Repunio',
    ],

    'negative_review' => [
        'subject' => ':rating★ review needs your attention',
        'intro' => 'A new review for :business needs your attention.',
        'col_author' => 'Author',
        'col_rating' => 'Rating',
        'col_review' => 'Review',
        'cta' => 'Reply now',
    ],

    'reply_failed' => [
        'subject' => 'We could not post your reply',
        'intro' => 'We tried to post a reply to a review for :business, but it failed.',
        'col_author' => 'Author',
        'col_review' => 'Review',
        'detail' => 'Please try posting the reply again from the app.',
        'cta' => 'Open reviews',
    ],

    'approvals_pending' => [
        'subject' => ':count repl(y/ies) waiting for approval',
        'intro' => 'You have :count repl(y/ies) waiting for your approval. Review and approve them so they can be posted.',
        'cta' => 'Review approvals',
    ],

    'review_goal' => [
        'subject_mid' => 'Your review goal: how the month is going',
        'subject_recap' => 'Review recap for :month',
        'intro_mid_ahead' => 'Great pace! You have :actual new reviews this month, ahead of the :expected expected by now (goal :goal). Keep it up.',
        'intro_mid_on_track' => 'You are on track: :actual new reviews this month, right around the :expected expected by now (goal :goal).',
        'intro_mid_behind' => 'A nudge: you have :actual new reviews this month, below the :expected expected by now (goal :goal). A little push helps.',
        'intro_recap' => 'Here is how :month finished: :actual new reviews against a goal of :goal.',
        'col_location' => 'Location',
        'col_goal' => 'Goal',
        'col_so_far' => 'So far',
        'col_projected' => 'Projected',
        'col_pace' => 'Pace',
        'col_got' => 'Got',
        'col_vs_goal' => 'vs goal',
        'col_vs_prev' => 'vs last month',
        'status_ahead' => 'Ahead',
        'status_on_track' => 'On track',
        'status_behind' => 'Behind',
        'cta' => 'View reviews',
    ],

    'coaching' => [
        'subject' => 'Your review goal: let\'s keep it going',
        'intro_almost' => 'So close! Just :remaining more to reach your goal of :goal this month. You\'ve got this!',
        'intro_behind' => 'You are at :actual of :goal this month. A steady push this week gets you back on pace. Here are a few ideas.',
        'intro_on_track' => 'Nice work! :actual of :goal and right on pace. A few asks this week keeps the momentum going.',
        'intro_ahead' => 'Great momentum! :actual of :goal, ahead of plan. Keep it rolling with these ideas.',
        'steady' => 'One thing: spread requests out over the days. A sudden flood of reviews looks suspicious to Google and can get filtered. Steady wins.',
        'cta' => 'Open reviews',
    ],

    'goal_reached' => [
        'subject' => 'Goal smashed! :goal reviews this month! 🎉',
        'intro' => 'Congratulations! You hit your goal of :goal new reviews this month! That is real momentum for your reputation.',
        'note' => 'Keep the habit going at a steady pace and next month will be even easier.',
        'cta' => 'Open reviews',
    ],

    'review_anomaly' => [
        'subject' => 'Heads up: :count thing(s) to check on your reviews',
        'intro' => 'We spotted something worth a look on your reviews:',
        'stalled' => 'no new reviews for :days days, though it is usually active.',
        'negative_streak' => ':count low-star reviews within 3 days. Reply quickly to limit the damage.',
        'spike' => 'unusual spike: :recent reviews in 7 days (normally about :baseline per week). Great news, or worth checking for spam.',
        'rating_drop' => 'rating is slipping: :recent★ recently vs :prior★ before.',
        'cta' => 'Open reviews',
    ],

    'invite' => [
        'subject' => 'You have been invited to join :workspace on Repunio',
        'greeting' => 'Hi,',
        'intro' => ':inviter invited you to join :workspace on Repunio as :role.',
        'note' => 'This invitation expires in 14 days. If you did not expect it, you can ignore this email.',
        'cta' => 'Accept invitation',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Every review, one inbox',
        'intro' => 'All reviews from your locations land in one inbox. Filter by rating, location or unanswered, and reply in two clicks.',
        'tip' => 'Try it now: open a review and press Generate with AI. You get a ready draft in your tone that you can edit before publishing.',
        'cta' => 'Open your reviews',
    ],
    'drip_automation' => [
        'subject' => 'Put replies on autopilot',
        'intro' => 'Create an AI agent that knows your business and tone, then let auto-reply rules answer routine reviews for you.',
        'tip' => 'Not ready for full autopilot? Use the approval queue: the AI drafts, you approve with one click.',
        'cta' => 'Set up automations',
    ],
    'drip_growth' => [
        'subject' => 'Collect more reviews this month',
        'intro' => 'Set a monthly review goal per location and we track the pace, cheer milestones and warn you about anomalies.',
        'tip' => 'Create your review collection page: a short link and QR code that send happy customers straight to your Google or TripAdvisor review form.',
        'cta' => 'Create your review page',
    ],
    'drip_reports' => [
        'subject' => 'Reports your clients will actually read',
        'intro' => 'Build a performance report from blocks: KPIs, AI summary, staff mentions, themes. Download as PDF or share a link.',
        'tip' => 'Set it once, send it monthly: schedule the report and it lands in inboxes automatically, in English or German.',
        'cta' => 'Build a report',
    ],
    'drip_team' => [
        'subject' => 'Bring your team on board',
        'intro' => 'Invite teammates with roles, or add guests who only receive notifications and reports, no login needed.',
        'tip' => 'Decide who gets which email under Settings, then route new-review alerts to the people who handle them.',
        'cta' => 'Invite your team',
    ],
    'drip_member' => [
        'subject' => 'Getting around Repunio',
        'intro' => 'You have been added to a workspace. The Reviews inbox is where the work happens: filter, reply, done.',
        'tip' => 'Set your interface and email language in your profile so everything arrives the way you like it.',
        'cta' => 'Open Repunio',
    ],
    'drip_unsubscribe' => 'Too many tips? :link',
    'drip_unsubscribe_link' => 'Unsubscribe from these emails',

    'unsubscribed_title' => 'You are unsubscribed',
    'unsubscribed_body' => 'You will no longer receive product tips and onboarding emails. Important account and billing emails still arrive. Changed your mind? Turn them back on in :link.',
    'unsubscribed_profile' => 'your profile',
];
