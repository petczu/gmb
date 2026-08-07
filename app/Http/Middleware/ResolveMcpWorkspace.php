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
 * Scopes an authenticated MCP request to the user's workspace(s). The endpoint
 * is a single /mcp (no workspace in the URL); the user has already been
 * resolved by `auth:api` (Passport OAuth). The consent screen binds the
 * connection to one or MORE workspaces; the first becomes the default tenancy
 * for the request, and tools may switch to any other allowed one via their
 * optional `workspace` argument (see ResolvesRequestedWorkspace). The full
 * allowed set is shared through the container as `mcp.allowed_workspaces`.
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

        // The workspaces the user picked for this connection on the consent
        // screen (keyed by the token's OAuth client), if still theirs.
        $boundIds = $this->boundWorkspaceIds($user->id, $user->token()?->oauth_client_id);

        $allowed = $this->resolveWorkspaces($workspaces, $boundIds, $billing);

        if ($allowed->isEmpty()) {
            return response()->json(['error' => 'MCP access requires the Pro plan.'], 403);
        }

        // Tools read this to offer/validate their `workspace` argument.
        app()->instance('mcp.allowed_workspaces', $allowed);

        $workspace = $allowed->first();
        tenancy()->initialize($workspace);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        auth()->setUser($user);

        return $next($request);
    }

    /**
     * The workspaces this request may operate on: every bound, MCP-enabled
     * workspace the user still belongs to (in consent order), falling back to
     * the user's first MCP-enabled workspace when nothing valid is bound.
     * Empty when no workspace has MCP access.
     *
     * @param  Collection<int, Workspace>  $workspaces
     * @param  array<int, string>  $boundIds
     * @return Collection<int, Workspace>
     */
    public function resolveWorkspaces(Collection $workspaces, array $boundIds, LocationBilling $billing): Collection
    {
        $bound = collect($boundIds)
            ->map(fn (string $id): ?Workspace => $workspaces->firstWhere('id', $id))
            ->filter(fn (?Workspace $w): bool => $w !== null && $billing->allows($w, Plans::MCP))
            ->values();

        if ($bound->isNotEmpty()) {
            return $bound;
        }

        return $workspaces
            ->filter(fn (Workspace $w): bool => $billing->allows($w, Plans::MCP))
            ->take(1)
            ->values();
    }

    /**
     * Kept for callers that only need one workspace (first of the allowed set).
     */
    public function resolveWorkspace(Collection $workspaces, ?string $boundId, LocationBilling $billing): ?Workspace
    {
        return $this->resolveWorkspaces($workspaces, $boundId === null ? [] : [$boundId], $billing)->first();
    }

    /**
     * The workspace ids bound to this OAuth client on the consent screen, in
     * the order they were saved. Empty when nothing was bound.
     *
     * @return array<int, string>
     */
    protected function boundWorkspaceIds(int $userId, ?string $oauthClientId): array
    {
        if ($oauthClientId === null) {
            return [];
        }

        return McpWorkspaceSelection::query()
            ->where('user_id', $userId)
            ->where('oauth_client_id', $oauthClientId)
            ->orderBy('id')
            ->pluck('workspace_id')
            ->all();
    }
}
