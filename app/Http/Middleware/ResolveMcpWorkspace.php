<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Billing\Plans;
use App\Models\McpWorkspaceSelection;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scopes an authenticated MCP request to the user's workspace. The endpoint is
 * a single /mcp (no workspace in the URL); the user has already been resolved
 * by `auth:api` (Passport OAuth). We use the workspace the user bound to this
 * OAuth client on the consent screen, falling back to their first MCP-enabled
 * (Pro) workspace, then gate the plan and initialize its tenancy so every tool
 * reads and writes strictly within its data.
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

        // The workspace the user picked for this connection on the consent
        // screen (keyed by the token's OAuth client), if it is still a
        // workspace they belong to.
        $boundId = $this->boundWorkspaceId($user->id, $user->token()?->oauth_client_id);

        $workspace = $this->resolveWorkspace($workspaces, $boundId, $billing);

        if ($workspace === null) {
            return response()->json(['error' => 'MCP access requires the Pro plan.'], 403);
        }

        tenancy()->initialize($workspace);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        auth()->setUser($user);

        return $next($request);
    }

    /**
     * Choose the workspace for this request: the bound one when it is a
     * MCP-enabled workspace the user belongs to, otherwise their first
     * MCP-enabled workspace. Null when none of them has MCP access.
     *
     * @param  Collection<int, Workspace>  $workspaces
     */
    public function resolveWorkspace(Collection $workspaces, ?string $boundId, LocationBilling $billing): ?Workspace
    {
        $bound = $boundId === null ? null : $workspaces->firstWhere('id', $boundId);

        if ($bound !== null && $billing->allows($bound, Plans::MCP)) {
            return $bound;
        }

        return $workspaces->first(fn (Workspace $w): bool => $billing->allows($w, Plans::MCP));
    }

    /**
     * The workspace id bound to this OAuth client on the consent screen, or
     * null when nothing was bound (or the client is unknown).
     */
    protected function boundWorkspaceId(int $userId, ?string $oauthClientId): ?string
    {
        if ($oauthClientId === null) {
            return null;
        }

        return McpWorkspaceSelection::query()
            ->where('user_id', $userId)
            ->where('oauth_client_id', $oauthClientId)
            ->value('workspace_id');
    }
}
