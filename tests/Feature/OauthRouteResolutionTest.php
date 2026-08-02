<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Guards the /oauth/* route split between Passport (MCP OAuth) and
 * FilamentSocialite. The Socialite route /oauth/{provider} used to swallow
 * Passport's /oauth/authorize (Sentry REPUNIO-H: ProviderNotConfigured
 * "authorize"); the {provider} pattern in AppServiceProvider::register()
 * keeps them apart.
 */
class OauthRouteResolutionTest extends TestCase
{
    public function test_oauth_routes_resolve_to_the_expected_controllers(): void
    {
        $cases = [
            ['GET', '/oauth/authorize', 'passport.authorizations.authorize'],
            ['POST', '/oauth/authorize', 'passport.authorizations.approve'],
            ['POST', '/oauth/token', 'passport.token'],
            ['GET', '/oauth/google', 'socialite.filament.app.oauth.redirect'],
            ['GET', '/oauth/linkedin-openid', 'socialite.filament.app.oauth.redirect'],
            ['GET', '/oauth/microsoft', 'socialite.filament.app.oauth.redirect'],
            ['GET', '/oauth/callback/google', 'oauth.callback'],
        ];

        foreach ($cases as [$method, $uri, $expectedRouteName]) {
            $route = $this->app['router']->getRoutes()->match(Request::create($uri, $method));

            $this->assertSame(
                $expectedRouteName,
                $route->getName(),
                "{$method} {$uri} resolved to [{$route->getName()}], expected [{$expectedRouteName}].",
            );
        }
    }
}
