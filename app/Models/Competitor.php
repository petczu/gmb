<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — a competitor business tracked against one of the workspace's
 * own locations. Rating/review counts come from the Google Places API and are
 * refreshed weekly by competitors:refresh.
 */
class Competitor extends Model
{
    protected $fillable = [
        'location_id', 'place_id', 'name', 'address',
        'rating', 'reviews_count', 'last_checked_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
