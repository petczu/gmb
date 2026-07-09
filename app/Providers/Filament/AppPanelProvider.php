<?php

namespace App\Providers\Filament;

use App\Filament\App\Auth\Register;
use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Pages\Profile;
use App\Http\Middleware\ApplyUserPreferences;
use App\Http\Middleware\EnsureBetaApproved;
use App\Http\Middleware\MarkOnboardingComplete;
use App\Http\Middleware\SetCurrentWorkspace;
use App\Http\Middleware\SetLocale;
use App\Models\Location;
use App\Models\SocialiteUser;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Auth\SocialiteUserProvisioner;
use App\Support\DemoDashboard;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('')
            ->default()
            ->login()
            // Self-service sign-up: creates the user + provisions their workspace,
            // then the onboarding guide walks them through setup. See Auth\Register.
            ->registration(Register::class)
            // Self-service MFA: TOTP authenticator (with recovery codes) and
            // emailed 6-digit codes. Not forced ($isRequired stays default false).
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->regenerableRecoveryCodes(),
                EmailAuthentication::make(),
            ])
            // Google OAuth sign-in + sign-up (login & register pages). New Google
            // users are auto-provisioned through the same flow as Auth\Register
            // (create user → workspace → session → WelcomeMail); existing users are
            // matched by email (account linking). See SocialiteUserProvisioner.
            ->plugin(
                FilamentSocialitePlugin::make()
                    ->providers(array_filter([
                        Provider::make('google')
                            // Translation KEY — localized per-request in the published
                            // vendor/filament-socialite/components/buttons.blade.php.
                            ->label('auth.continue_google')
                            ->icon('heroicon-o-globe-alt')
                            ->scopes(['openid', 'profile', 'email']),
                        // LinkedIn/Microsoft buttons appear once their app
                        // credentials are configured (no dead buttons before).
                        filled(config('services.linkedin-openid.client_id'))
                            ? Provider::make('linkedin-openid')
                                ->label('auth.continue_linkedin')
                                ->icon('heroicon-o-briefcase')
                                ->scopes(['openid', 'profile', 'email'])
                            : null,
                        filled(config('services.microsoft.client_id'))
                            ? Provider::make('microsoft')
                                ->label('auth.continue_microsoft')
                                ->icon('heroicon-o-window')
                            : null,
                    ]))
                    ->registration(true)
                    ->userModelClass(User::class)
                    ->socialiteUserModelClass(SocialiteUser::class)
                    ->createUserUsing(fn (string $provider, \Laravel\Socialite\Contracts\User $oauthUser) => app(SocialiteUserProvisioner::class)->create($oauthUser))
                    ->resolveUserUsing(fn (string $provider, \Laravel\Socialite\Contracts\User $oauthUser) => User::query()->where('email', $oauthUser->getEmail())->first()),
            )
            ->brandName('Repunio')
            // Full wordmark when expanded; icon-only when collapsed/mobile.
            // Light + dark variants (Filament shows the matching one per theme).
            ->brandLogo(fn (): HtmlString => new HtmlString(view('filament.logo', ['theme' => 'light'])->render()))
            ->darkModeBrandLogo(fn (): HtmlString => new HtmlString(view('filament.logo', ['theme' => 'dark'])->render()))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon/favicon.ico'))
            // SPA navigation → top progress bar on every page change (loading cue).
            // The Google-connect flow must be a FULL browser navigation: SPA mode
            // fetches links via wire:navigate, and a fetch that gets redirected
            // to accounts.google.com dies on CORS.
            ->spa()
            ->spaUrlExceptions(fn (): array => [
                url('/connect/google'),
                url('/connect/google').'/*',
            ])
            // Collapsible left sidebar on desktop.
            ->sidebarCollapsibleOnDesktop()
            // Brand primary derived from the logo colour (#2d19ec).
            ->colors([
                'primary' => Color::hex('#2d19ec'),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverClusters(in: app_path('Filament/App/Clusters'), for: 'App\Filament\App\Clusters')
            ->pages([
                Dashboard::class,
            ])
            // Pillar-based navigation, mirroring Localith's IA. Dashboard stays
            // ungrouped at the top; remaining pillars (Posts, Reports, SEO) are
            // added as those features land.
            ->navigationGroups([
                NavigationGroup::make('Listings')->label(fn (): string => __('nav.group_listings')),
                NavigationGroup::make('Reviews')->label(fn (): string => __('nav.group_reviews')),
                // Reports is a CLUSTER (single ungrouped nav item, no group
                // heading) — a "Reports" group above a lone "Reports" item
                // read as a duplicate.
                NavigationGroup::make('Settings')->label(fn (): string => __('nav.group_settings')),
            ])
            // Top-right user (avatar) menu.
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => __('nav.my_profile'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (): string => Profile::getUrl()),
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->widgets([
                // Dashboard widgets are auto-discovered from Filament/App/Widgets
                // (ReviewStatsOverview, StarDistributionChart, RatingTrendChart,
                // LatestReviews) and ordered by their $sort.
            ])
            // Full favicon set in <head> (->favicon() only sets one).
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('partials.favicons')->render(),
            )
            // Button colour overrides.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('partials.panel-styles')->render(),
            )
            // Subscription paywall: blocks the app when there's no active plan
            // or a payment problem (after the grace window). See SubscriptionGate.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.billing-gate')->render(),
            )
            // Floating "Ask AI" chat launcher (bottom-right, Intercom style).
            // Hidden during first-run onboarding: there's no data to ask about yet.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => tenancy()->initialized
                    && ! (tenant() instanceof Workspace && tenant()->isOnboarding())
                    && (auth()->user()?->can('view_reviews') ?? false)
                    ? view('filament.app.ask-ai-launcher')->render()
                    : '',
            )
            // Global search is disabled; the workspace switcher takes its place
            // on the right of the top bar, left of the user avatar menu.
            ->globalSearch(false)
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.app.workspace-switcher')->render(),
            )
            // Auth pages (login/register/password reset) always render in the
            // light theme; the user's dark-mode preference applies only inside
            // the app. Runs after Filament's own theme bootstrap in <head>.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => request()->routeIs('filament.app.auth.*')
                    ? '<script>(function(){var el=document.documentElement;var strip=function(){if(el.classList.contains("dark")){el.classList.remove("dark");}};strip();el.style.colorScheme="light";new MutationObserver(strip).observe(el,{attributes:true,attributeFilter:["class"]});})();</script>'
                    : '',
            )
            // While the workspace has no locations, the dashboard widgets show
            // DEMO data (see DemoDashboard) and this small connect-first invite
            // floats on top. Filters stay hidden until the first location.
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => DemoDashboard::active()
                    ? view('filament.app.dashboard-demo-overlay')->render()
                    : '',
                scopes: Dashboard::class,
            )
            // Language switcher + legal links under the auth (login/register) card.
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_END,
                fn (): string => view('partials.auth-footer')->render(),
            )
            // filament-socialite only hooks its buttons into the LOGIN form —
            // mirror them on the registration form (same divider + providers).
            ->renderHook(
                PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
                fn (): string => Blade::render(
                    '<x-filament-socialite::buttons :show-divider="true" />',
                ),
            )
            // Split-screen auth hero (left half, desktop): latest-update promo
            // on the login page, product pitch on the registration page.
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('partials.auth-hero', ['mode' => 'login'])->render(),
                scopes: Login::class,
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('partials.auth-hero', ['mode' => 'register'])->render(),
                scopes: Register::class,
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Apply the visitor's language choice on guest pages too (login,
                // register) — ApplyUserPreferences only runs once authenticated.
                SetLocale::class,
                SetCurrentWorkspace::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureBetaApproved::class,
                ApplyUserPreferences::class,
                MarkOnboardingComplete::class,
            ]);
    }
}
