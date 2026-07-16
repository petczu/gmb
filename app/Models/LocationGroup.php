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

    /**
     * The group a location currently belongs to, if any. A location lives in at
     * most one group (mirrors a competitor belonging to one battle).
     */
    public static function forLocation(int $locationId): ?self
    {
        return static::query()
            ->whereJsonContains('location_ids', $locationId)
            ->orderBy('name')
            ->first();
    }

    /**
     * Remove a location from every group it appears in, deleting any group left
     * empty by the removal.
     */
    public static function detachLocation(int $locationId): void
    {
        foreach (static::query()->whereJsonContains('location_ids', $locationId)->get() as $group) {
            $remaining = array_values(array_filter($group->locationIds(), fn (int $id): bool => $id !== $locationId));

            if ($remaining === []) {
                $group->delete();

                continue;
            }

            $group->update(['location_ids' => $remaining]);
        }
    }
}
