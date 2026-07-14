<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — one (expanded) day of an external calendar event. Multi-day
 * ICS events are stored as one row per covered day, so the calendar grid can
 * group by plain date.
 *
 * @property Carbon $date
 * @property string $title
 */
class ExternalCalendarEvent extends Model
{
    protected $fillable = ['external_calendar_id', 'date', 'title'];

    protected $casts = ['date' => 'date'];

    /** @return BelongsTo<ExternalCalendar, $this> */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(ExternalCalendar::class, 'external_calendar_id');
    }
}
