<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TENANT model — a named competitor "battle": a group of the workspace's own
 * locations compared against a group of competitor places. Ratings are
 * aggregated weighted by review count (see CompetitorTrends). A plain 1-vs-1 is
 * a battle with one own location and one competitor place.
 */
class CompetitorBattle extends Model
{
    protected $fillable = ['name', 'own_location_ids'];

    protected $casts = [
        'own_location_ids' => 'array',
    ];

    /**
     * @return HasMany<Competitor, $this>
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class, 'battle_id');
    }

    /** @return list<int> */
    public function ownLocationIds(): array
    {
        return array_values(array_map('intval', (array) ($this->own_location_ids ?? [])));
    }

    /** The workspace's own locations on this battle's side. */
    public function ownLocations()
    {
        return Location::query()->whereIn('id', $this->ownLocationIds())->get();
    }
}
