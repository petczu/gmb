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
 * Scopes an authenticated MCP request to the user's workspace. The endpoint is
 * a single /mcp (no workspace in the URL); the user has already been resolved
 * by `auth:api` (Passport OAuth). We pick their first MCP-enabled (Pro)
 * workspace, gate the plan and initialize its tenancy so every tool reads and
 * writes strictly within its data.
 */
class ResolveMcpWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $workspaces = $user->workspaces()->get();

        if ($workspaces->isEmpty()) {
            return response()->json(['error' => 'You do not belong to any workspace.'], 403);
        }

        $billing = app(LocationBilling::class);

        // Prefer a workspace that actually has MCP access; fall back to the
        // first so the response can explain the Pro requirement clearly.
        $workspace = $workspaces->first(fn (Workspace $w): bool => $billing->allows($w, Plans::MCP))
            ?? $workspaces->first();

        if (! $billing->allows($workspace, Plans::MCP)) {
            return response()->json(['error' => 'MCP access requires the Pro plan.'], 403);
        }

        tenancy()->initialize($workspace);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        auth()->setUser($user);

        return $next($request);
    }
}
