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
 * satisfied, so onboarding disappears on the next request without the user
 * having to click a final button. While onboarding is still running, page
 * loads are redirected to the full-screen /onboarding wizard (except the
 * pages the wizard's steps need). Runs after SetCurrentWorkspace, so tenancy
 * is already initialized for the current workspace.
 */
class MarkOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = session('current_workspace_id');

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);

            if ($workspace?->isOnboarding()) {
                if (app(OnboardingStatus::class)->complete($workspace)) {
                    $workspace->onboarding_completed_at = now();
                    $workspace->save();
                } elseif ($this->shouldRedirectToWizard($request)) {
                    return redirect('/onboarding');
                }
            }
        }

        return $next($request);
    }

    /**
     * Only full HTML page loads are redirected; Livewire updates are POST and
     * the wizard's step pages (Stripe/Google round-trips, company, billing,
     * locations) must stay reachable.
     */
    private function shouldRedirectToWizard(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->expectsJson()
            && ! $request->is(
                'onboarding*',
                'company*',
                'billing*',
                'profile*',
                'locations*',
                'connect-location*',
                'connect/*',
                'livewire*',
            );
    }
}
