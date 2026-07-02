<?php

declare(strict_types=1);

return [
    // OnboardingStatus steps
    'step_company_label' => 'Add your company details',
    'step_company_hint' => 'Country and billing info used on invoices and reports.',
    'step_plan_label' => 'Choose a plan',
    'step_plan_hint' => 'Start your 14-day free trial, no card required.',
    'step_location_label' => 'Connect your first location',
    'step_location_hint' => 'Link a Google Business Profile to start pulling reviews.',

    // Setup wizard (/onboarding)
    'wizard_title' => 'Set up your workspace',
    'wiz_plan_done' => '✓ Your plan is active — continue to the next step.',
    'wiz_plan_pick' => 'Pick a plan',
    'wiz_interval' => 'Billing interval',
    'wiz_monthly' => 'Monthly',
    'wiz_yearly' => 'Yearly',
    'wiz_start_trial' => 'Start 14-day free trial',
    'wiz_plan_required' => 'Start your trial first — no card required.',
    'wiz_location_body' => 'Link your Google Business Profile so we can pull in your reviews. You will be redirected to Google to authorize access, then pick the location to connect.',
    'wiz_connect_google' => 'Connect Google Business Profile',
    'wiz_per_location' => 'per location / month',
    'wiz_plan_desc_starter' => 'Reviews inbox, manual replies and basic reports.',
    'wiz_plan_desc_growth' => 'Adds AI auto-replies, scheduled reports and comparisons.',
    'wiz_plan_desc_pro' => 'Everything, plus white label, API, MCP and client access.',

    // Onboarding overlay
    'welcome_title' => 'Welcome, let\'s set up your account',
    'welcome_subtitle' => 'A few quick steps and you\'re ready to go.',
    'continue_step' => 'Continue: :label',
    'enter_app' => 'Enter app →',
    'sign_out' => 'Sign out',

    // Pending-deletion overlay
    'deletion_title' => 'This workspace is scheduled for deletion',
    'deletion_body' => 'All data will be permanently deleted on <strong>:date</strong>. You can still cancel and keep your workspace.',
    'cancel_deletion' => 'Cancel deletion',

    // Grace banner
    'grace_banner' => '⚠️ We couldn\'t process your last payment. Your service stays active until <strong>:date</strong>, please',
    'update_your_billing' => 'update your billing',

    // Paywall overlay
    'payment_problem_title' => 'There’s a problem with your payment',
    'needs_plan_title' => 'Choose a plan to get started',
    'payment_problem_body' => 'Your access is paused because we couldn’t process payment. Update your billing to continue.',
    'needs_plan_body' => 'Pick a plan to activate reviews, AI replies and reports for your locations. 14-day free trial.',
    'update_billing' => 'Update billing',
    'view_plans' => 'View plans',

    // Connect-select-location page
    'connecting_location' => 'Connecting location…',
    'choose_location' => 'Choose which Google Business location to connect to this workspace.',
    'could_not_load' => 'Could not load locations',
    'back' => 'Back',
    'no_locations_available' => 'No locations available',
    'no_locations_body' => 'No Google Business locations were returned. They may still be loading on Google\'s side, try again shortly.',
    'connect_then_done' => 'Connect one or more locations, then click Done.',
    'done' => 'Done',
    'connected' => 'Connected',
    'connect' => 'Connect',
    'connecting' => 'Connecting…',

    // ConnectSelectLocation page (notifications + title)
    'select_location_title' => 'Select business location',
    'connect_failed' => 'Could not connect location',
    'connected_title' => 'Connected: :name',
    'connected_body' => 'Reviews are syncing in the background, they will appear on the Locations page shortly.',
    'location_fallback' => 'location',
];
