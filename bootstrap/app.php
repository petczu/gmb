<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a TLS-terminating proxy (Cloudflare tunnel / load balancer):
        // honor X-Forwarded-Proto so Laravel + Livewire generate https URLs
        // (otherwise the app sees plain http and the browser blocks mixed content).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        // Stripe + Postmark + Zernio post to their webhooks without a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'postmark/*',
            'zernio/*',
        ]);

        // No default `login` route exists (Filament owns auth); send guests to
        // the app panel login instead of crashing with RouteNotFoundException.
        $middleware->redirectGuestsTo(fn () => route('filament.app.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render API + MCP failures as JSON. For MCP this is essential: an
        // unauthenticated request must return 401 (not a 302 to login) so the
        // package can attach the WWW-Authenticate header that bootstraps OAuth.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('mcp/*'),
        );
    })->create();
