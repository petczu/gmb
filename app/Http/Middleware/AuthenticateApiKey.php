<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Billing\Plans;
use App\Models\ApiKey;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a REST request by its workspace API key (Bearer), gates the Pro
 * plan and initializes that workspace's tenancy. The resolved key is stashed on
 * the request for per-route ability checks (RequireApiAbility).
 */
class AuthenticateApiKey
{
    public const REQUEST_KEY = 'api_key';

    public function handle(Request $request, Closure $next): Response
    {
        $raw = (string) $request->bearerToken();
        $key = $raw === '' ? null : ApiKey::findByRawKey($raw);

        if ($key === null) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        $workspace = Workspace::find($key->workspace_id);

        if ($workspace === null) {
            return response()->json(['message' => 'Workspace not found.'], 401);
        }

        if (! app(LocationBilling::class)->allows($workspace, Plans::API)) {
            return response()->json(['message' => 'API access requires the Pro plan.'], 403);
        }

        tenancy()->initialize($workspace);
        $key->markUsed();
        $request->attributes->set(self::REQUEST_KEY, $key);

        return $next($request);
    }
}
