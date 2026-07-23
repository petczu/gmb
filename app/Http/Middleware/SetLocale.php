<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale for guests and logged-out pages (login, register,
 * legal pages): a signed-in user's stored locale wins, otherwise the visitor's
 * session choice from the language switcher. Runs early so translations render
 * in the chosen language even before authentication.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (! in_array($locale, Locales::codes(), true)) {
            $locale = $request->session()->get('locale');
        }

        if (in_array($locale, Locales::codes(), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
