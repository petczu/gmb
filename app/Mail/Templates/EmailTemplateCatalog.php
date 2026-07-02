<?php

declare(strict_types=1);

namespace App\Mail\Templates;

/**
 * The registry of editable email templates. Each entry carries a title,
 * category, sample data (for live preview / test sends) and the default subject
 * + markdown body per locale, composed from the existing translation strings so
 * the seed stays in sync with the shipped copy. After seeding, the DB row is the
 * source of truth and is freely editable in the admin panel.
 *
 * Body conventions:
 *   :token            scalar placeholder, substituted from the email's data
 *   {{ button:Label }} branded call-to-action button linking to :url
 */
class EmailTemplateCatalog
{
    /** Locales the templates are maintained in. */
    public const LOCALES = ['en', 'de'];

    /**
     * @return array<string, array{title: string, category: string, sample: array<string, string>}>
     */
    public static function all(): array
    {
        return [
            'welcome' => ['title' => 'Welcome', 'category' => 'Onboarding', 'sample' => ['name' => 'Peter', 'url' => self::url()]],
            'invite' => ['title' => 'Team invitation', 'category' => 'Team', 'sample' => ['inviter' => 'Peter', 'workspace' => 'Acme Agency', 'role' => 'admin', 'url' => self::url('invite/abc')]],
            'trial_ending' => ['title' => 'Trial ending', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'days' => '3', 'date' => 'July 11, 2026', 'url' => self::url('billing')]],
            'payment_succeeded' => ['title' => 'Payment received', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'amount' => '€24.00 EUR', 'url' => self::url('billing')]],
            'payment_failed' => ['title' => 'Payment failed', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'days' => '7', 'url' => self::url('billing')]],
            'ai_limit' => ['title' => 'AI limit reached', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'plan' => 'Growth', 'url' => self::url('billing')]],
            'auto_recharge_failed' => ['title' => 'Auto top-up failed', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'url' => self::url('billing')]],
            'subscription_canceled' => ['title' => 'Subscription canceled', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'date' => '11. July 2026', 'url' => self::url('billing')]],
            'subscription_resumed' => ['title' => 'Subscription resumed', 'category' => 'Billing', 'sample' => ['name' => 'Peter', 'url' => self::url('billing')]],
            'account_disconnected' => ['title' => 'Account disconnected', 'category' => 'Operations', 'sample' => ['name' => 'Peter', 'account' => 'Acme Google', 'url' => self::url('locations')]],
            'sync_restored' => ['title' => 'Sync restored', 'category' => 'Operations', 'sample' => ['name' => 'Peter', 'account' => 'Acme Google', 'url' => self::url('locations')]],
            'approvals_pending' => ['title' => 'Approvals pending', 'category' => 'Operations', 'sample' => ['name' => 'Peter', 'count' => '4', 'url' => self::url('approvals')]],

            'new_reviews' => ['title' => 'New reviews digest', 'category' => 'Reputation', 'sample' => ['name' => 'Peter', 'count' => '3', 'location' => 'GAME OVER Vienna', 'url' => self::url('reviews')]],
            'negative_review' => ['title' => 'Negative review', 'category' => 'Reputation', 'sample' => ['name' => 'Peter', 'business' => 'GAME OVER Vienna', 'rating' => '2', 'url' => self::url('reviews')]],
            'reply_failed' => ['title' => 'Reply failed', 'category' => 'Operations', 'sample' => ['name' => 'Peter', 'business' => 'GAME OVER Vienna', 'url' => self::url('reviews')]],
            'review_anomaly' => ['title' => 'Anomaly alert', 'category' => 'Review growth', 'sample' => ['name' => 'Peter', 'count' => '3', 'url' => self::url('reviews')]],
            'review_goal_mid' => ['title' => 'Goal progress (mid-month)', 'category' => 'Review growth', 'sample' => ['name' => 'Peter', 'intro' => 'Great pace! You have 18 new reviews this month, ahead of the 14 expected by now (goal 30). Keep it up.', 'url' => self::url('reviews')]],
            'review_goal_recap' => ['title' => 'Goal recap (month end)', 'category' => 'Review growth', 'sample' => ['name' => 'Peter', 'month' => 'May 2026', 'intro' => 'Here is how May 2026 finished: 28 new reviews against a goal of 30.', 'url' => self::url('reviews')]],
            'review_coaching' => ['title' => 'Goal coaching (weekly)', 'category' => 'Review growth', 'sample' => ['name' => 'Peter', 'intro' => 'You are at 18 of 100 this month. A steady push this week gets you back on pace. Here are a few ideas.', 'url' => self::url('reviews')]],
            'review_goal_reached' => ['title' => 'Goal reached 🎉', 'category' => 'Review growth', 'sample' => ['name' => 'Peter', 'goal' => '100', 'url' => self::url('reviews')]],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    /** @return array<string, string> sample data for preview / test sends */
    public static function sample(string $key): array
    {
        return self::all()[$key]['sample'] ?? [];
    }

    /** The scalar placeholder tokens available in a template body (":name", ...). */
    public static function placeholders(string $key): array
    {
        return array_map(
            fn (string $name): string => ':'.$name,
            array_values(array_filter(array_keys(self::sample($key)), fn (string $k): bool => $k !== 'url')),
        );
    }

    /**
     * Pre-rendered block HTML (token => HTML) for the live preview of dynamic
     * templates, using representative sample data.
     *
     * @return array<string, string>
     */
    public static function sampleBlocks(string $key, string $locale = 'en'): array
    {
        $t = fn (string $k): string => __('emails.'.$k, [], $locale);

        return match ($key) {
            'new_reviews' => ['table' => EmailBlocks::reviews([
                ['author' => 'Darthpixi', 'rating' => 5, 'location' => 'GAME OVER Vienna', 'snippet' => 'Absolutely fantastic, so much fun!'],
                ['author' => 'Jaqueline Janour', 'rating' => 5, 'location' => 'GAME OVER Vienna', 'snippet' => 'Loved it, will come back for sure.'],
            ])],
            'negative_review' => ['table' => EmailBlocks::reviews([
                ['author' => 'Cornel Tom', 'rating' => 2, 'snippet' => 'Long wait and the room felt rushed.'],
            ])],
            'reply_failed' => ['table' => EmailBlocks::reviews([
                ['author' => 'Cornel Tom', 'snippet' => 'Long wait and the room felt rushed.'],
            ])],
            'review_anomaly' => ['items' => EmailBlocks::list([
                '<strong>GAME OVER Vienna</strong>: '.e(__('emails.review_anomaly.stalled', ['days' => 17], $locale)),
                '<strong>Branch 2</strong>: '.e(__('emails.review_anomaly.negative_streak', ['count' => 4], $locale)),
            ])],
            'review_goal_mid' => ['table' => EmailBlocks::progressBar(18, 30).EmailBlocks::stats([
                ['title' => 'GAME OVER Vienna', 'rows' => [
                    ['label' => $t('review_goal.col_goal'), 'value' => '30'],
                    ['label' => $t('review_goal.col_so_far'), 'value' => '18'],
                    ['label' => $t('review_goal.col_projected'), 'value' => '31'],
                    ['label' => $t('review_goal.col_pace'), 'value' => e($t('review_goal.status_ahead'))],
                ]],
            ])],
            'review_goal_recap' => ['table' => EmailBlocks::progressBar(28, 30).EmailBlocks::stats([
                ['title' => 'GAME OVER Vienna', 'rows' => [
                    ['label' => $t('review_goal.col_goal'), 'value' => '30'],
                    ['label' => $t('review_goal.col_got'), 'value' => '28'],
                    ['label' => $t('review_goal.col_vs_goal'), 'value' => '93%'],
                    ['label' => $t('review_goal.col_vs_prev'), 'value' => EmailBlocks::trend(6)],
                ]],
            ])],
            'review_coaching' => ['tips' => EmailBlocks::progressBar(18, 100).EmailBlocks::list(\App\Support\ReviewTips::pick('entertainment', 3, 0, $locale))],
            default => [],
        };
    }

    public static function defaultSubject(string $key, string $locale): string
    {
        return match ($key) {
            'invite' => __('emails.invite.subject', ['workspace' => ':workspace'], $locale),
            'trial_ending' => __('emails.trial_ending.subject', ['days' => ':days'], $locale),
            'approvals_pending' => __('emails.approvals_pending.subject', ['count' => ':count'], $locale),
            'new_reviews' => __('emails.new_reviews.subject', ['count' => ':count'], $locale),
            'negative_review' => __('emails.negative_review.subject', ['rating' => ':rating'], $locale),
            'review_anomaly' => __('emails.review_anomaly.subject', ['count' => ':count'], $locale),
            'review_goal_mid' => __('emails.review_goal.subject_mid', [], $locale),
            'review_goal_recap' => __('emails.review_goal.subject_recap', ['month' => ':month'], $locale),
            'review_coaching' => __('emails.coaching.subject', [], $locale),
            'review_goal_reached' => __('emails.goal_reached.subject', ['goal' => ':goal'], $locale),
            default => __('emails.'.$key.'.subject', [], $locale),
        };
    }

    public static function defaultBody(string $key, string $locale): string
    {
        return match ($key) {
            'welcome' => self::shell($locale, self::greeting($locale), [
                __('emails.welcome.intro', [], $locale),
                __('emails.welcome.next', [], $locale),
            ], __('emails.welcome.cta', [], $locale)),

            'invite' => self::shell($locale, __('emails.invite.greeting', [], $locale), [
                __('emails.invite.intro', ['inviter' => ':inviter', 'workspace' => ':workspace', 'role' => ':role'], $locale),
                __('emails.invite.note', [], $locale),
            ], __('emails.invite.cta', [], $locale)),

            'trial_ending' => self::shell($locale, self::greeting($locale), [
                __('emails.trial_ending.intro', ['date' => ':date'], $locale),
                __('emails.trial_ending.note', [], $locale),
            ], __('emails.trial_ending.cta', [], $locale)),

            'payment_succeeded' => self::shell($locale, self::greeting($locale), [
                __('emails.payment_succeeded.intro', ['amount' => ':amount'], $locale),
            ], __('emails.payment_succeeded.cta', [], $locale)),

            'payment_failed' => self::shell($locale, self::greeting($locale), [
                __('emails.payment_failed.intro', ['days' => ':days'], $locale),
            ], __('emails.payment_failed.cta', [], $locale)),

            'ai_limit' => self::shell($locale, self::greeting($locale), [
                __('emails.ai_limit.intro', ['plan' => ':plan'], $locale),
            ], __('emails.ai_limit.cta', [], $locale)),

            'auto_recharge_failed' => self::shell($locale, self::greeting($locale), [
                __('emails.auto_recharge_failed.intro', [], $locale),
            ], __('emails.auto_recharge_failed.cta', [], $locale)),

            'subscription_canceled' => self::shell($locale, self::greeting($locale), [
                __('emails.subscription_canceled.intro', ['date' => ':date'], $locale),
                __('emails.subscription_canceled.note', [], $locale),
            ], __('emails.subscription_canceled.cta', [], $locale)),

            'subscription_resumed' => self::shell($locale, self::greeting($locale), [
                __('emails.subscription_resumed.intro', [], $locale),
            ], __('emails.subscription_resumed.cta', [], $locale)),

            'account_disconnected' => self::shell($locale, self::greeting($locale), [
                __('emails.account_disconnected.intro', ['account' => ':account'], $locale),
                __('emails.account_disconnected.detail', [], $locale),
            ], __('emails.account_disconnected.cta', [], $locale)),

            'sync_restored' => self::shell($locale, self::greeting($locale), [
                __('emails.sync_restored.intro', ['account' => ':account'], $locale),
            ], __('emails.sync_restored.cta', [], $locale)),

            'approvals_pending' => self::shell($locale, self::greeting($locale), [
                __('emails.approvals_pending.intro', ['count' => ':count'], $locale),
            ], __('emails.approvals_pending.cta', [], $locale)),

            'new_reviews' => self::shell($locale, self::greeting($locale), [
                __('emails.new_reviews.intro', ['count' => ':count', 'location' => ':location'], $locale),
                '{{ table }}',
            ], __('emails.new_reviews.cta', [], $locale)),

            'negative_review' => self::shell($locale, self::greeting($locale), [
                __('emails.negative_review.intro', ['business' => ':business'], $locale),
                '{{ table }}',
            ], __('emails.negative_review.cta', [], $locale)),

            'reply_failed' => self::shell($locale, self::greeting($locale), [
                __('emails.reply_failed.intro', ['business' => ':business'], $locale),
                '{{ table }}',
                __('emails.reply_failed.detail', [], $locale),
            ], __('emails.reply_failed.cta', [], $locale)),

            'review_anomaly' => self::shell($locale, self::greeting($locale), [
                __('emails.review_anomaly.intro', [], $locale),
                '{{ items }}',
            ], __('emails.review_anomaly.cta', [], $locale)),

            'review_goal_mid', 'review_goal_recap' => self::shell($locale, self::greeting($locale), [
                ':intro',
                '{{ table }}',
            ], __('emails.review_goal.cta', [], $locale)),

            'review_coaching' => self::shell($locale, self::greeting($locale), [
                ':intro',
                '{{ tips }}',
                __('emails.coaching.steady', [], $locale),
            ], __('emails.coaching.cta', [], $locale)),

            'review_goal_reached' => self::shell($locale, self::greeting($locale), [
                __('emails.goal_reached.intro', ['goal' => ':goal'], $locale),
                __('emails.goal_reached.note', [], $locale),
            ], __('emails.goal_reached.cta', [], $locale)),

            default => '',
        };
    }

    private static function greeting(string $locale): string
    {
        return __('emails.greeting', ['name' => ':name'], $locale);
    }

    /**
     * Assemble a standard body: greeting, paragraphs, CTA button, sign-off.
     *
     * @param  list<string>  $paragraphs
     */
    private static function shell(string $locale, string $greeting, array $paragraphs, string $buttonLabel): string
    {
        $blocks = array_merge(
            [$greeting],
            $paragraphs,
            ['{{ button:'.$buttonLabel.' }}'],
            [__('emails.signoff', [], $locale)."\n".__('emails.team', [], $locale)],
        );

        return implode("\n\n", $blocks);
    }

    private static function url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
