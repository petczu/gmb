<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Private beta gate for the app panel: signed-in users whose application has
 * not been activated yet are sent to the "request received" page instead of
 * the app. Registered right after Authenticate so it runs before onboarding
 * and preference middleware. See Services\Auth\BetaAccess.
 */
class EnsureBetaApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->hasBetaAccess()) {
            return $next($request);
        }

        // Logging out must always work from the pending screen.
        if ($request->routeIs('filament.app.auth.logout')) {
            return $next($request);
        }

        return redirect()->route('beta.pending');
    }
}
