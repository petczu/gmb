<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Services\Onboarding\OnboardingStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Flips a workspace's onboarding to "complete" the moment all its steps are
 * satisfied, so the onboarding overlay disappears on the next request without
 * the user having to click a final button. Runs after SetCurrentWorkspace, so
 * tenancy is already initialized for the current workspace.
 */
class MarkOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = session('current_workspace_id');

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);

            if ($workspace?->isOnboarding() && app(OnboardingStatus::class)->complete($workspace)) {
                $workspace->onboarding_completed_at = now();
                $workspace->save();
            }
        }

        return $next($request);
    }
}
