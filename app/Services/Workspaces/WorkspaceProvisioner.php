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
            'slug' => $this->uniqueSlug(),
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

    /**
     * Anonymous random slug ("ws-x7k2m9qe4t") instead of a name-derived one:
     * no PII in URLs/logs (the slug appears in the MCP endpoint and console
     * commands) and no awkward "-2" suffixes on common names.
     */
    private function uniqueSlug(): string
    {
        do {
            $slug = 'ws-'.Str::lower(Str::random(10));
        } while (Workspace::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
