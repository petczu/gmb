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
            ['POST', '/oauth/authorize/confirm', 'mcp.oauth.approve'],
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

    public function test_social_callback_without_state_redirects_to_login_instead_of_500(): void
    {
        // Crawlers hit /oauth/callback/google with no encrypted `state`, which
        // FilamentSocialite cannot decode (Sentry REPUNIO-5). It must not 500.
        $this->get('/oauth/callback/google')
            ->assertRedirect(route('filament.app.auth.login'));
    }
}
