<?php

declare(strict_types=1);

namespace App\Services\Workspaces;

use App\Console\Commands\RolesSyncCommand;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PermissionCatalog;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates a brand-new workspace (tenant) and makes the given user its owner.
 *
 * Creating the Workspace fires stancl's TenantCreated pipeline, which creates
 * the tenant database and runs its migrations synchronously. We then attach the
 * owner in the central workspace_user pivot and seed the team-scoped
 * Owner/Admin/Member roles (same definitions as the roles:sync command).
 */
class WorkspaceProvisioner
{
    public function create(User $owner, string $companyName): Workspace
    {
        $companyName = trim($companyName) ?: ($owner->name."'s workspace");

        $workspace = Workspace::create([
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'slug' => $this->uniqueSlug($companyName),
        ]);

        $workspace->users()->attach($owner->id, [
            'role' => 'owner',
            'membership_type' => 'internal',
        ]);

        $this->seedRoles($workspace, $owner);

        return $workspace;
    }

    /** Seed team-scoped roles + give the owner the 'owner' role. */
    private function seedRoles(Workspace $workspace, User $owner): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Permissions are global capability names (team_id = null).
        $registrar->setPermissionsTeamId(null);
        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Roles are scoped to this workspace (team_id = workspace id).
        $registrar->setPermissionsTeamId($workspace->id);
        foreach (RolesSyncCommand::roles() as $roleName => $permissions) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'team_id' => $workspace->id])
                ->syncPermissions($permissions);
        }

        $owner->unsetRelation('roles');
        $owner->syncRoles(['owner']);

        $registrar->forgetCachedPermissions();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 1;

        while (Workspace::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
