<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Workspace;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class RolesSyncCommand extends Command
{
    protected $signature = 'roles:sync';

    protected $description = 'Seed permissions + default Owner/Admin/Member roles for EVERY workspace (team-scoped) and align member roles with the membership pivot';

    /**
     * Role → permissions (the granular catalog lives in PermissionCatalog).
     * Owner = all; Admin = all but billing; Member = operational. Guest has NO
     * permissions: it is a notification-only contact (selectable as an email
     * recipient) with no app access.
     */
    public static function roles(): array
    {
        $all = PermissionCatalog::all();

        return [
            'owner' => $all,
            'admin' => array_values(array_diff($all, ['manage_billing'])),
            'member' => ['view_reviews', 'manage_reviews', 'view_reports', 'view_competitors'],
            'guest' => [],
        ];
    }

    public function handle(PermissionRegistrar $registrar): int
    {
        // Permissions are global capability names (the table has no team_id).
        $registrar->setPermissionsTeamId(null);
        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Drop any legacy global roles (team_id = null) so the only roles are
        // team-scoped (per workspace). Assignments are re-created from the pivot.
        Role::query()->whereNull('team_id')->each(fn (Role $r) => $r->delete());

        foreach (Workspace::query()->get() as $workspace) {
            $this->ensureWorkspaceRoles($registrar, $workspace);
        }

        $registrar->forgetCachedPermissions();
        $this->info('Roles & permissions synced for all workspaces.');

        return self::SUCCESS;
    }

    /** Create the default roles for one workspace and align members from the pivot. */
    public function ensureWorkspaceRoles(PermissionRegistrar $registrar, Workspace $workspace): void
    {
        $registrar->setPermissionsTeamId($workspace->id);

        foreach (self::roles() as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'team_id' => $workspace->id]);
            $role->syncPermissions($permissions);
        }

        foreach ($workspace->users()->get() as $user) {
            $pivotRole = $user->pivot->role ?: 'member';
            if (! array_key_exists($pivotRole, self::roles())) {
                $pivotRole = 'member';
            }
            $user->unsetRelation('roles');
            $user->syncRoles([$pivotRole]);
        }

        $this->line("[{$workspace->slug}] roles seeded + ".$workspace->users()->count().' member(s) aligned');
    }
}
