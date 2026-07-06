<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Sentry\State\Scope;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session-based tenancy initialization.
 *
 * Reads the selected workspace id from the session and initializes stancl
 * tenancy for it. We intentionally do NOT use domain identification (clean
 * URLs like /reviews). This MUST also be registered as a Livewire
 * persistent middleware (see AppServiceProvider) so the tenant survives
 * Livewire AJAX updates — otherwise modals/typing/drag lose the tenant and
 * you get "No workspace selected". See gmb-gotchas.
 */
class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = session('current_workspace_id');

        // Auto-select the user's first workspace when none is chosen yet, so the
        // app panel is immediately usable after login. A proper switcher can
        // override this by writing `current_workspace_id` to the session.
        if (! $workspaceId && $request->user()) {
            $first = $request->user()->workspaces()->first();
            if ($first) {
                $workspaceId = $first->id;
                session(['current_workspace_id' => $workspaceId]);
            }
        }

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);

            if ($workspace) {
                // Re-initialize only if not already on this tenant.
                if (! tenancy()->initialized || tenant('id') !== $workspace->id) {
                    tenancy()->initialize($workspace);
                }

                // Scope spatie roles/permissions to this workspace (teams).
                app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

                // Tag Sentry events with the workspace so multi-tenant errors
                // are attributable. No-op while the DSN is unset.
                \Sentry\configureScope(function (Scope $scope) use ($workspace, $request): void {
                    $scope->setTag('workspace', $workspace->slug ?? $workspace->id);
                    if ($user = $request->user()) {
                        $scope->setUser(['id' => (string) $user->id]);
                    }
                });
            } else {
                // Stale id (workspace deleted) — drop it and stay central.
                session()->forget('current_workspace_id');
                tenancy()->end();
            }
        } elseif (tenancy()->initialized) {
            tenancy()->end();
        }

        return $next($request);
    }
}
