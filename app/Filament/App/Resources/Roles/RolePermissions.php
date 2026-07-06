<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles;

use App\Support\PermissionCatalog;

/**
 * Shared mapping between the grouped permission checkboxes (perms_<group>
 * form keys) and the flat permission list stored on the role.
 */
class RolePermissions
{
    /**
     * @return list<string>
     */
    public static function formKeys(): array
    {
        return array_map(fn (string $g): string => 'perms_'.$g, array_keys(PermissionCatalog::groups()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function extract(array $data): array
    {
        $selected = [];
        foreach (array_keys(PermissionCatalog::groups()) as $group) {
            $selected = array_merge($selected, array_values((array) ($data['perms_'.$group] ?? [])));
        }

        return array_values(array_intersect($selected, PermissionCatalog::all()));
    }

    /**
     * @param  list<string>  $permissions  the role's current permission names
     * @return array<string, list<string>>
     */
    public static function fill(array $permissions): array
    {
        $state = [];
        foreach (PermissionCatalog::groups() as $group => $groupPermissions) {
            $state['perms_'.$group] = array_values(array_intersect($groupPermissions, $permissions));
        }

        return $state;
    }
}
