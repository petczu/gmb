<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — a competitor place inside a CompetitorBattle. Rating/review
 * counts come from the Google Places API (or a connected location's synced
 * data) and are refreshed daily by competitors:refresh. location_id is kept as
 * the battle's primary own location for back-compat (reports, own-growth).
 */
class Competitor extends Model
{
    protected $fillable = [
        'battle_id', 'location_id', 'place_id', 'name', 'address', 'latitude', 'longitude',
        'rating', 'reviews_count', 'rating_distribution', 'last_checked_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'rating_distribution' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'last_checked_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<CompetitorBattle, $this>
     */
    public function battle(): BelongsTo
    {
        return $this->belongsTo(CompetitorBattle::class, 'battle_id');
    }
}
