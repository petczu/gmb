<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the signed-in user's display preferences for the request — currently
 * the timezone Filament uses to render dates/times. (Storage stays UTC.)
 */
class ApplyUserPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($tz = $request->user()?->timezone) {
            FilamentTimezone::set($tz);
        }

        if (in_array($locale = $request->user()?->locale, ['en', 'de'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
