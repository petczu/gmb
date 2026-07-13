<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LegalDocument;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After the Terms get a new published version, signed-in users must read and
 * accept it before continuing to use the app: everything redirects to the
 * review screen until they do. Runs after EnsureBetaApproved on the app panel.
 */
class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        // Logging out must always work from the review screen.
        if ($request->routeIs('filament.app.auth.logout')) {
            return $next($request);
        }

        $current = LegalDocument::currentVersion(LegalDocument::TERMS);

        if ($current === 0 || (int) $user->getAttribute('terms_version') >= $current) {
            return $next($request);
        }

        return redirect()->route('terms.review');
    }
}
