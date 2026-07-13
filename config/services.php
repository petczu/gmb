<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
        // Secret in the webhook URL path that authenticates Postmark callbacks.
        'webhook_secret' => env('POSTMARK_WEBHOOK_SECRET'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // Places API (New) key for competitor benchmarking; empty = disabled.
        'places_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    // Social logins (filament-socialite). Buttons appear only for providers
    // whose credentials are configured.
    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', '/oauth/callback/linkedin-openid'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', '/oauth/callback/microsoft'),
        // 'common' = both personal and work/school Microsoft accounts.
        'tenant' => env('MICROSOFT_TENANT', 'common'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'reviews' => [
        // 'fake' (seeded sample data) | 'zernio' (live Zernio API)
        'driver' => env('REVIEWS_DRIVER', 'fake'),
        // Single shared Zernio API key for the whole platform (one key in ENV).
        // Clients connect their Google accounts under this key via OAuth.
        'zernio_key' => env('ZERNIO_API_KEY'),
        // Base URL ends with /v1; the SDK paths already include /v1 (host is
        // stripped of a trailing /v1 when applied). Leave empty to use the
        // SDK's built-in host.
        'zernio_base_url' => env('ZERNIO_BASE_URL'),
        // Shared secret used to verify the HMAC-SHA256 signature on inbound
        // Zernio webhooks (X-Zernio-Signature). Both keys read the same env var.
        'webhook_secret' => env('ZERNIO_WEBHOOK_SECRET'),
        'zernio_webhook_secret' => env('ZERNIO_WEBHOOK_SECRET'),
        // Google posts + listing editing use Zernio's NATIVE REST API
        // (see https://zernio.com/openapi.yaml) — same base URL and API key
        // as the review sync, Bearer auth. No extra credentials.
    ],

    'ai' => [
        // 'fake' (templated, no key) | 'claude' (Anthropic Messages API)
        'driver' => env('AI_DRIVER', 'fake'),
        'key' => env('ANTHROPIC_API_KEY'),
        // Default to the most capable Opus; switch to claude-sonnet-4-6 or
        // claude-haiku-4-5 for cheaper short, high-volume replies.
        'model' => env('AI_MODEL', 'claude-opus-4-8'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'max_tokens' => (int) env('AI_MAX_TOKENS', 400),
        'timeout' => (int) env('AI_TIMEOUT', 30),
        // Credits charged per AI generation: 1 per reply, 5 per report.
        'reply_credits' => (int) env('AI_REPLY_CREDITS', 1),
        'report_credits' => (int) env('AI_REPORT_CREDITS', 5),
        // Global monthly USD ceiling for system-wide AI spend. Alert-only:
        // super-admins get an email at 80% and 100%. Empty = no alerts.
        'monthly_budget_usd' => env('AI_MONTHLY_BUDGET_USD'),

        // Per-user hourly cap on interactive UI generations (agent test runs,
        // description drafts). Generous on purpose: it only exists to stop a
        // runaway script or stuck retry loop, not to meter humans. 0 = off.
        'ui_generation_rate_limit' => (int) env('AI_UI_GENERATION_RATE_LIMIT', 100),

        // List prices (USD per 1,000,000 tokens) used to compute the real cost
        // of each AI call for the usage ledger. VERIFY against current Anthropic
        // pricing; override per model via env if needed. 'default' is the
        // fallback for unknown models.
        'pricing' => [
            'claude-opus-4-8' => ['input' => 15.0, 'output' => 75.0],
            'claude-sonnet-4-6' => ['input' => 3.0, 'output' => 15.0],
            'claude-haiku-4-5' => ['input' => 1.0, 'output' => 5.0],
            'default' => ['input' => 3.0, 'output' => 15.0],
        ],
    ],

    'pdf' => [
        // Optional explicit Chrome/Chromium binary for Browsershot. Leave null
        // to use the puppeteer-managed chrome-headless-shell. On macOS dev you
        // can point this at the system Chrome to skip the puppeteer download.
        'chrome_path' => env('PDF_CHROME_PATH'),
    ],

    'marketing' => [
        // The marketing site — canonical home of the legal pages.
        'url' => env('MARKETING_URL', 'https://repunio.com'),
    ],

    'billing' => [
        // Stripe recurring prices per LOCATION/month for each plan. The location
        // subscription's quantity tracks the number of connected locations; the
        // active price determines the plan (see App\Billing\Plans).
        'prices' => [
            'starter' => env('STRIPE_PRICE_STARTER'),
            'growth' => env('STRIPE_PRICE_GROWTH'),
            'pro' => env('STRIPE_PRICE_PRO'),
        ],
        // Optional yearly (−20%) prices — enables the Monthly/Yearly toggle.
        'prices_yearly' => [
            'starter' => env('STRIPE_PRICE_STARTER_YEARLY'),
            'growth' => env('STRIPE_PRICE_GROWTH_YEARLY'),
            'pro' => env('STRIPE_PRICE_PRO_YEARLY'),
        ],
        // Credit top-ups: a single Stripe per-credit price (one-time) bought with
        // a custom quantity (see App\Billing\Credits). Credits are spent once the
        // plan's monthly allowance is exhausted (1 reply = 1 credit, 1 report = 5)
        // and never expire. An empty price id hides the top-up UI.
        'credit_price_id' => env('STRIPE_PRICE_CREDIT'),
        'credit_price' => (float) env('CREDIT_PRICE', 0.08),
        'credit_min' => (int) env('CREDIT_MIN', 10),
        'credit_max' => (int) env('CREDIT_MAX', 5000),         // hard cap for the custom amount input
        'credit_slider_max' => (int) env('CREDIT_SLIDER_MAX', 500), // slider quick-range max
        // Volume discount: a second Stripe per-credit price (e.g. -10%) used when
        // the quantity reaches the threshold. Empty id = no discount.
        'credit_price_id_volume' => env('STRIPE_PRICE_CREDIT_VOLUME'),
        'credit_volume_threshold' => (int) env('CREDIT_VOLUME_THRESHOLD', 500),
        'credit_volume_discount' => (int) env('CREDIT_VOLUME_DISCOUNT', 10), // percent
        'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),
        // Days the service keeps working after a failed payment (dunning grace).
        'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),
        // Stripe Tax auto-calculation at Checkout (needs Stripe Tax set up).
        'automatic_tax' => env('BILLING_AUTOMATIC_TAX', false),
    ],

    'account' => [
        // GDPR: days a deleted workspace stays recoverable ("Cancel deletion")
        // before the scheduled job irreversibly purges it (drops the tenant DB).
        'deletion_grace_days' => (int) env('ACCOUNT_DELETION_GRACE_DAYS', 30),
    ],

];
