<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes an authenticated MCP request to the workspace named in the URL
 * (/mcp/{workspace}). The user has already been resolved by `auth:api`
 * (Passport OAuth); here we verify they belong to that workspace, gate the Pro
 * plan and initialize the workspace's tenancy so every tool reads and writes
 * strictly within its data.
 */
class ResolveMcpWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $slug = (string) $request->route('workspace');
        $workspace = $slug === '' ? null : Workspace::query()->where('slug', $slug)->first();

        if ($workspace === null) {
            return response()->json(['error' => 'Workspace not found.'], 404);
        }

        if (! $workspace->users()->whereKey($user->getKey())->exists()) {
            return response()->json(['error' => 'You do not have access to this workspace.'], 403);
        }

        if (! app(LocationBilling::class)->allows($workspace, Plans::MCP)) {
            return response()->json(['error' => 'MCP access requires the Pro plan.'], 403);
        }

        tenancy()->initialize($workspace);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        auth()->setUser($user);

        return $next($request);
    }
}
