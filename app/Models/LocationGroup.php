<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a named group of the workspace's own locations. Selectable in
 * the dashboard/report location filter, where it expands to its member
 * locations, and used to organize the Locations list by cluster.
 */
class LocationGroup extends Model
{
    protected $fillable = ['name', 'location_ids'];

    protected $casts = [
        'location_ids' => 'array',
    ];

    /** @return list<int> */
    public function locationIds(): array
    {
        return array_values(array_map('intval', (array) ($this->location_ids ?? [])));
    }
}
