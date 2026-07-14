<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Horizon metrics snapshot (queue/job throughput graphs in the dashboard).
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Email out any report schedules that are due (daily check; the schedule's own
// frequency/send_day decides whether it actually fires).
Schedule::command('reports:send-scheduled')->dailyAt('07:00');

// GDPR: irreversibly purge workspaces whose 30-day deletion grace has elapsed.
Schedule::command('workspaces:purge-deleted')->dailyAt('03:00');

// Remind workspace owners 3 days before their free trial ends.
Schedule::command('subscriptions:trial-reminders')->dailyAt('09:00');

// Keep reviews fresh and trigger new-review digest emails (the first sync of a
// location backfills its history and is intentionally not emailed).
Schedule::command('reviews:sync')->hourly();

// Safety net for the review-reply AUTOMATIONS: the webhook dispatches
// RunReviewAutomation per new review; this pass catches sync-discovered
// reviews and failed jobs. --since guards a freshly connected location's
// backlog from being mass-replied (only reviews from the last 48h).
Schedule::command('automations:run --since=48')->everyFifteenMinutes();

// Post auto-replies whose "organic" scheduled time has arrived.
Schedule::command('auto-reply:post-due')->everyFiveMinutes();

// Remind owners daily about replies that have been waiting >24h for approval.
Schedule::command('auto-reply:approvals-reminder')->dailyAt('08:00');

// Auto top-up AI credits for opted-in workspaces below their threshold.
Schedule::command('ai:auto-recharge')->everyFifteenMinutes();

// Review-growth emails: monthly goal pace (15th + 1st) and anomaly alerts
// (stalled / negative streak / spike / rating drop). The command's own date
// gates and per-location cooldowns decide what actually sends.
Schedule::command('reviews:insights')->dailyAt('10:00');

// Push bulk hours edits scheduled for today (early, before opening time).
Schedule::command('listings:apply-scheduled')->dailyAt('00:20');

// Onboarding email series: one due step per user per day (see DripSeries).
Schedule::command('emails:drip')->dailyAt('10:00');

// Daily competitor rating/review snapshot via the Google Places API (no-op
// while GOOGLE_PLACES_API_KEY is unset). Daily granularity feeds the trends
// on the Competitors page; cost is ~1 details call per competitor per day.
Schedule::command('competitors:refresh')->dailyAt('06:00');

// Global AI budget guard: emails super-admins at 80%/100% of
// AI_MONTHLY_BUDGET_USD (no-op while unset).
Schedule::command('ai:budget-check')->dailyAt('09:30');
