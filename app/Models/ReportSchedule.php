<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ActivityLog\ActivityLogger;
use App\Services\Notifications\NotificationRecipients;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * TENANT model — a recurring email delivery of the performance report.
 */
class ReportSchedule extends Model
{
    protected $fillable = [
        'name',
        'enabled',
        'frequency',
        'send_day',
        'period',
        'language',
        'location_id',
        'location_ids',
        'compare',
        'blocks',
        'recipients',
        'last_sent_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'compare' => 'boolean',
        'blocks' => 'array',
        'location_ids' => 'array',
        'send_day' => 'integer',
        'recipients' => 'array',
        'last_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // location_ids (multi) is the source of truth; the legacy single
        // location_id column mirrors a one-location selection so nothing that
        // still reads it goes stale after an edit.
        static::saving(function (ReportSchedule $schedule): void {
            $ids = array_values(array_map('intval', (array) $schedule->location_ids));
            $schedule->location_ids = $ids ?: null;
            $schedule->location_id = count($ids) === 1 ? $ids[0] : null;
        });

        // Deletion happens through Filament's stock DeleteAction (table + edit
        // page), so the activity feed hook lives on the model.
        static::deleted(function (ReportSchedule $schedule): void {
            ActivityLogger::log('schedule.deleted', ['name' => $schedule->name]);
        });
    }

    /**
     * Is this schedule due to send on the given day? Daily-runner semantics:
     * monthly fires on send_day of the month, weekly on send_day (ISO dow),
     * and never twice within the same period.
     */
    public function isDue(CarbonInterface $now): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($this->frequency === 'weekly') {
            $dayMatches = $now->dayOfWeekIso === max(1, min(7, $this->send_day));
            $alreadySent = $this->last_sent_at !== null && $this->last_sent_at->greaterThanOrEqualTo($now->copy()->startOfWeek());

            return $dayMatches && ! $alreadySent;
        }

        // monthly (clamp send_day to the last day of short months)
        $day = min($this->send_day, $now->daysInMonth);
        $dayMatches = $now->day === $day;
        $alreadySent = $this->last_sent_at !== null && $this->last_sent_at->greaterThanOrEqualTo($now->copy()->startOfMonth());

        return $dayMatches && ! $alreadySent;
    }

    /**
     * @return array<int, string>
     */
    public function resolveRecipients(Workspace $workspace): array
    {
        $recipients = (array) ($this->recipients ?? []);

        // Legacy shape: a flat list of email strings (no include/exclude/emails
        // keys). Treat it as an explicit external-email list.
        $isStructured = array_key_exists('include', $recipients)
            || array_key_exists('exclude', $recipients)
            || array_key_exists('emails', $recipients);

        if (! $isStructured) {
            $flat = array_values(array_filter($recipients, fn ($e): bool => is_string($e) && $e !== ''));

            return $flat !== [] ? $flat : $this->allMemberEmails($workspace);
        }

        $emails = array_values(array_filter((array) ($recipients['emails'] ?? []), fn ($e): bool => is_string($e) && $e !== ''));

        // Role/member selection (Included minus Excluded), resolved to emails.
        $memberEmails = [];
        $include = array_values((array) ($recipients['include'] ?? []));
        if ($include !== []) {
            $members = $this->workspaceMembers($workspace);
            $ids = app(NotificationRecipients::class)->resolveIds(
                ['include' => $include, 'exclude' => array_values((array) ($recipients['exclude'] ?? []))],
                $members,
            );
            $memberEmails = $members->whereIn('id', $ids)->pluck('email')->filter()->all();
        }

        $all = array_values(array_unique(array_merge($memberEmails, $emails)));

        // Nothing configured at all → fall back to every workspace member.
        return $all !== [] ? $all : $this->allMemberEmails($workspace);
    }

    /**
     * The workspace's members. Extracted as a seam so recipient resolution can
     * be unit-tested without provisioning the central membership tables.
     *
     * @return Collection<int, User>
     */
    protected function workspaceMembers(Workspace $workspace): Collection
    {
        return $workspace->users()->get();
    }

    /** @return array<int, string> */
    private function allMemberEmails(Workspace $workspace): array
    {
        return $this->workspaceMembers($workspace)->pluck('email')->filter()->values()->all();
    }
}
