<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ReviewAnomalyMail;
use App\Mail\ReviewCoachingMail;
use App\Mail\ReviewGoalProgressMail;
use App\Mail\ReviewGoalReachedMail;
use App\Models\Workspace;
use App\Services\Notifications\ChatChannels;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reviews\ReviewInsightsService;
use App\Services\Webhooks\WebhookDispatcher;
use App\Support\ReviewTips;
use App\Webhooks\WebhookEvents;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Daily review-growth notifications: monthly goal pace (mid-month + a
 * start-of-month recap of the month just ended) and anomaly alerts (stalled,
 * negative streak, volume spike, rating drop). Runs per workspace inside its
 * tenancy; dedup and per-location cooldowns are stored as flags on the central
 * workspace `data` JSON column, so no extra table is needed.
 */
class SendReviewInsightsCommand extends Command
{
    protected $signature = 'reviews:insights {workspace? : Workspace id or slug; omit for all} {--force : Ignore date gates and cooldowns (manual/testing)}';

    protected $description = 'Send review-goal progress and anomaly alert emails';

    /** Per-location cooldown for repeating the same anomaly type. */
    private const ALERT_COOLDOWN_DAYS = 7;

    /** Anomaly type => the workspace toggle that enables it. */
    private const ANOMALY_TOGGLES = [
        'stalled' => 'notify_stalled',
        'negative_streak' => 'notify_negative_streak',
        'spike' => 'notify_spike',
        'rating_drop' => 'notify_rating_drop',
    ];

    public function handle(): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $this->forWorkspace($workspace);
            } catch (Throwable $e) {
                Log::warning('Review insights failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        return self::SUCCESS;
    }

    private function forWorkspace(Workspace $workspace): void
    {
        $reviewsUrl = rtrim((string) config('app.url'), '/').'/reviews';
        $insights = app(ReviewInsightsService::class);
        $now = CarbonImmutable::now();
        $force = (bool) $this->option('force');

        $this->sendGoalEmails($workspace, $insights, $reviewsUrl, $now, $force);
        $this->sendAnomalyEmail($workspace, $insights, $reviewsUrl, $now, $force);
        $this->sendCoaching($workspace, $insights, $reviewsUrl, $now, $force);
        $this->fireWebhooks($workspace, $insights, $now, $force);
    }

    /**
     * Outbound webhooks for goal.reached / anomaly.detected. Independent of the
     * email notification toggles (webhooks are a separate channel) but deduped
     * the same way: goal once per month, each anomaly on a per-type cooldown.
     */
    private function fireWebhooks(
        Workspace $workspace,
        ReviewInsightsService $insights,
        CarbonImmutable $now,
        bool $force,
    ): void {
        $dispatcher = app(WebhookDispatcher::class);

        if ($insights->hasAnyGoal()) {
            $data = $insights->goalProgress();
            $goal = (int) $data['total_goal'];
            $actual = (int) $data['total_actual'];

            if ($goal > 0 && $actual >= $goal && $this->claim($workspace, 'webhook_goal_'.$now->format('Y_m'), $force)) {
                $dispatcher->dispatch(WebhookEvents::GOAL_REACHED, [
                    'goal' => $goal,
                    'actual' => $actual,
                    'month' => $now->format('Y-m'),
                ]);

                ChatChannels::send(
                    $workspace,
                    NotificationCategory::REVIEW_GROWTH,
                    'goal_reached',
                    ['goal' => $goal, 'actual' => $actual],
                );
            }
        }

        foreach ($insights->anomalies() as $anomaly) {
            $key = 'webhook_anomaly_'.$anomaly['type'].'_'.$anomaly['location_id'];

            if (! $force && $this->onCooldown($workspace, $key, $now)) {
                continue;
            }

            $workspace->setAttribute($key, $now->toDateString());
            $workspace->save();

            $dispatcher->dispatch(WebhookEvents::ANOMALY_DETECTED, $anomaly);

            ChatChannels::send(
                $workspace,
                NotificationCategory::REVIEW_GROWTH,
                'anomaly',
                ['location' => (string) $anomaly['location'], 'description' => (string) ($anomaly['detail'] ?? $anomaly['type'])],
            );
        }
    }

    /**
     * Weekly motivational coaching (Mondays, while a goal is active and not yet
     * reached) plus a one-off celebration when the workspace hits its goal.
     */
    private function sendCoaching(
        Workspace $workspace,
        ReviewInsightsService $insights,
        string $reviewsUrl,
        CarbonImmutable $now,
        bool $force,
    ): void {
        if (! $this->enabled($workspace, 'notify_coaching') || ! $insights->hasAnyGoal()) {
            return;
        }

        $data = $insights->goalProgress();
        $goal = (int) $data['total_goal'];
        $actual = (int) $data['total_actual'];

        if ($goal <= 0) {
            return;
        }

        // Milestone: goal reached — celebrate once per month.
        if ($actual >= $goal) {
            if ($this->claim($workspace, 'coaching_reached_'.$now->format('Y_m'), $force)) {
                $this->deliver($workspace, fn (string $name, string $lang) => new ReviewGoalReachedMail($name, $goal, $reviewsUrl, $lang), 'goal reached');
            }

            return;
        }

        // Weekly nudge with category-based tips (Mondays), once per ISO week.
        if (($force || $now->dayOfWeekIso === 1) && $this->claim($workspace, 'coaching_week_'.$now->format('o_W'), $force)) {
            $category = $workspace->business_category;
            $week = (int) $now->isoWeek;

            $this->deliver($workspace, fn (string $name, string $lang) => new ReviewCoachingMail(
                name: $name,
                intro: $this->coachingIntro($data, $lang),
                tips: ReviewTips::pick($category, 3, $week, $lang),
                reviewsUrl: $reviewsUrl,
                lang: $lang,
                actual: $actual,
                goal: $goal,
            ), 'coaching');
        }
    }

    /**
     * Adaptive coaching headline: almost there / behind / ahead / on track.
     *
     * @param  array<string, mixed>  $data
     */
    private function coachingIntro(array $data, string $lang): string
    {
        $goal = (int) $data['total_goal'];
        $actual = (int) $data['total_actual'];
        $remaining = max(0, $goal - $actual);
        $params = ['actual' => $actual, 'goal' => $goal, 'remaining' => $remaining];

        $key = match (true) {
            $remaining <= (int) ceil($goal * 0.2) => 'almost',
            ($data['status'] ?? '') === 'behind' => 'behind',
            ($data['status'] ?? '') === 'ahead' => 'ahead',
            default => 'on_track',
        };

        return __('emails.coaching.intro_'.$key, $params, $lang);
    }

    private function sendGoalEmails(
        Workspace $workspace,
        ReviewInsightsService $insights,
        string $reviewsUrl,
        CarbonImmutable $now,
        bool $force,
    ): void {
        if (! $this->enabled($workspace, 'notify_goal_progress') || ! $insights->hasAnyGoal()) {
            return;
        }

        // Mid-month pace check (around the 15th).
        if (($force || $now->day === 15) && $this->claim($workspace, 'insights_goal_mid_'.$now->format('Y_m'), $force)) {
            $data = $insights->goalProgress();
            if ($data['rows'] !== []) {
                $this->deliver($workspace, fn (string $name, string $lang) => new ReviewGoalProgressMail($name, 'mid', $data, $reviewsUrl, $lang), 'goal mid');
            }
        }

        // Start-of-month recap of the month that just ended (around the 1st).
        if (($force || $now->day === 1) && $this->claim($workspace, 'insights_goal_recap_'.$now->subMonth()->format('Y_m'), $force)) {
            $data = $insights->recap($now->subMonth());
            if ($data['rows'] !== []) {
                $this->deliver($workspace, fn (string $name, string $lang) => new ReviewGoalProgressMail($name, 'recap', $data, $reviewsUrl, $lang), 'goal recap');
            }
        }
    }

    private function sendAnomalyEmail(
        Workspace $workspace,
        ReviewInsightsService $insights,
        string $reviewsUrl,
        CarbonImmutable $now,
        bool $force,
    ): void {
        $fresh = [];
        $stamp = [];

        foreach ($insights->anomalies() as $anomaly) {
            $toggle = self::ANOMALY_TOGGLES[$anomaly['type']] ?? null;
            if ($toggle === null || ! $this->enabled($workspace, $toggle)) {
                continue;
            }

            $key = 'insights_alert_'.$anomaly['type'].'_'.$anomaly['location_id'];
            if (! $force && $this->onCooldown($workspace, $key, $now)) {
                continue;
            }

            $fresh[] = $anomaly;
            $stamp[$key] = $now->toDateString();
        }

        if ($fresh === []) {
            return;
        }

        // Stamp cooldowns before sending so a mail failure can't cause a same-run retry storm.
        foreach ($stamp as $key => $date) {
            $workspace->setAttribute($key, $date);
        }
        $workspace->save();

        $this->deliver($workspace, fn (string $name, string $lang) => new ReviewAnomalyMail($name, $fresh, $reviewsUrl, $lang), count($fresh).' anomalies');
    }

    /** A toggle defaults to ON until the user explicitly turns it off. */
    private function enabled(Workspace $workspace, string $toggle): bool
    {
        $value = $workspace->getAttribute($toggle);

        return $value === null ? true : (bool) $value;
    }

    /** Claim a once-per-period send; returns false if already claimed (unless forced). */
    private function claim(Workspace $workspace, string $flag, bool $force): bool
    {
        if (! $force && $workspace->getAttribute($flag)) {
            return false;
        }

        $workspace->setAttribute($flag, true);
        $workspace->save();

        return true;
    }

    private function onCooldown(Workspace $workspace, string $key, CarbonImmutable $now): bool
    {
        $last = $workspace->getAttribute($key);

        return $last !== null && CarbonImmutable::parse($last)->greaterThan($now->subDays(self::ALERT_COOLDOWN_DAYS));
    }

    private function deliver(Workspace $workspace, \Closure $build, string $label): void
    {
        app(NotificationDispatcher::class)->dispatch(
            $workspace,
            NotificationCategory::REVIEW_GROWTH,
            $build,
        );

        $this->info(sprintf('[%s] sent %s', $workspace->slug, $label));
    }
}
