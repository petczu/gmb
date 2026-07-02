<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

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
        'location_id',
        'compare',
        'recipients',
        'last_sent_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'compare' => 'boolean',
        'send_day' => 'integer',
        'recipients' => 'array',
        'last_sent_at' => 'datetime',
    ];

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
        $explicit = array_values(array_filter((array) ($this->recipients ?? []), fn ($e): bool => is_string($e) && $e !== ''));

        if ($explicit !== []) {
            return $explicit;
        }

        return $workspace->users()->pluck('email')->filter()->values()->all();
    }
}
