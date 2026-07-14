<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TENANT model — an external ICS feed (public Google Calendar, holidays,
 * bookings) overlaid on the posts calendar. Events live in
 * external_calendar_events and are replaced wholesale on each sync.
 *
 * @property string $name
 * @property string $url
 * @property string $color
 * @property bool $enabled
 * @property ?Carbon $synced_at
 */
class ExternalCalendar extends Model
{
    protected $fillable = ['name', 'url', 'color', 'enabled', 'synced_at', 'sync_error'];

    protected $casts = ['enabled' => 'boolean', 'synced_at' => 'datetime'];

    /** @return HasMany<ExternalCalendarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ExternalCalendarEvent::class);
    }
}
