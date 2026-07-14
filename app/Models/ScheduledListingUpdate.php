<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a bulk hours edit parked until a future date (e.g. the new
 * winter schedule starting Jan 1). listings:apply-scheduled pushes it to the
 * Google profiles on the morning of apply_on and stamps applied_at.
 *
 * @property list<int> $location_ids
 * @property ?array<int, array<string, mixed>> $opening_hours
 * @property ?array<int, array<string, mixed>> $special_hours
 * @property Carbon $apply_on
 */
class ScheduledListingUpdate extends Model
{
    protected $fillable = [
        'location_ids', 'opening_hours', 'special_hours', 'apply_on',
        'applied_at', 'error', 'created_by', 'created_by_name',
    ];

    protected $casts = [
        'location_ids' => 'array',
        'opening_hours' => 'array',
        'special_hours' => 'array',
        'apply_on' => 'date',
        'applied_at' => 'datetime',
    ];
}
