<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class RolesSyncCommand extends Command
{
    protected $signature = 'roles:sync';

    protected $description = 'Seed permissions + default Owner/Admin/Member roles for EVERY workspace (team-scoped) and align member roles with the membership pivot';

    /** Permissions gating the app's features. */
    public const PERMISSIONS = [
        'manage_locations',   // connect / disconnect locations
        'manage_reviews',     // reply / edit / delete replies
        'manage_automations', // create/run automations + AI agents
        'manage_reports',     // create report schedules
        'view_reports',       // open Reports / dashboard
        'manage_team',        // invite users, assign roles
        'manage_billing',     // subscription + credits
    ];

    /**
     * Role → permissions. Owner = all; Admin = all but billing; Member =
     * operational. Guest has NO permissions: it is a notification-only contact
     * (selectable as an email recipient) with no app access.
     */
    public const ROLES = [
        'owner' => self::PERMISSIONS,
        'admin' => [
            'manage_locations', 'manage_reviews', 'manage_automations',
            'manage_reports', 'view_reports', 'manage_team',
        ],
        'member' => ['manage_reviews', 'view_reports'],
        'guest' => [],
    ];

    public function handle(PermissionRegistrar $registrar): int
    {
        // Permissions are global capability names (the table has no team_id).
        $registrar->setPermissionsTeamId(null);
        foreach (self::PERMISSIONS as $name) {
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

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'team_id' => $workspace->id]);
            $role->syncPermissions($permissions);
        }

        foreach ($workspace->users()->get() as $user) {
            $pivotRole = $user->pivot->role ?: 'member';
            if (! array_key_exists($pivotRole, self::ROLES)) {
                $pivotRole = 'member';
            }
            $user->unsetRelation('roles');
            $user->syncRoles([$pivotRole]);
        }

        $this->line("[{$workspace->slug}] roles seeded + ".$workspace->users()->count().' member(s) aligned');
    }
}
