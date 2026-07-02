<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind one of the API key's granted scopes (ApiAbilities).
 * Runs after AuthenticateApiKey, which stashes the resolved key on the request.
 */
class RequireApiAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $key = $request->attributes->get(AuthenticateApiKey::REQUEST_KEY);

        if (! $key instanceof ApiKey || ! $key->hasAbility($ability)) {
            return response()->json(['message' => "This API key is missing the required scope: {$ability}."], 403);
        }

        return $next($request);
    }
}
