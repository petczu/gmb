<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Http\Middleware\SetCurrentWorkspace;
use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Pages\Profile;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->registration(\App\Filament\App\Auth\Register::class)
            // Self-service MFA: TOTP authenticator (with recovery codes) and
            // emailed 6-digit codes. Not forced ($isRequired stays default false).
            ->multiFactorAuthentication([
                \Filament\Auth\MultiFactor\App\AppAuthentication::make()
                    ->recoverable()
                    ->regenerableRecoveryCodes(),
                \Filament\Auth\MultiFactor\Email\EmailAuthentication::make(),
            ])
            // Google OAuth sign-in + sign-up (login & register pages). New Google
            // users are auto-provisioned through the same flow as Auth\Register
            // (create user → workspace → session → WelcomeMail); existing users are
            // matched by email (account linking). See SocialiteUserProvisioner.
            ->plugin(
                \DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin::make()
                    ->providers([
                        \DutchCodingCompany\FilamentSocialite\Provider::make('google')
                            // Translation KEY — localized per-request in the published
                            // vendor/filament-socialite/components/buttons.blade.php.
                            ->label('auth.continue_google')
                            ->icon('heroicon-o-globe-alt')
                            ->scopes(['openid', 'profile', 'email']),
                    ])
                    ->registration(true)
                    ->userModelClass(\App\Models\User::class)
                    ->socialiteUserModelClass(\App\Models\SocialiteUser::class)
                    ->createUserUsing(fn (string $provider, \Laravel\Socialite\Contracts\User $oauthUser) => app(\App\Services\Auth\SocialiteUserProvisioner::class)->create($oauthUser))
                    ->resolveUserUsing(fn (string $provider, \Laravel\Socialite\Contracts\User $oauthUser) => \App\Models\User::query()->where('email', $oauthUser->getEmail())->first()),
            )
            ->brandName('Repunio')
            // Full wordmark when expanded; icon-only when collapsed/mobile.
            // Light + dark variants (Filament shows the matching one per theme).
            ->brandLogo(fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(view('filament.logo', ['theme' => 'light'])->render()))
            ->darkModeBrandLogo(fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(view('filament.logo', ['theme' => 'dark'])->render()))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon/favicon.ico'))
            // SPA navigation → top progress bar on every page change (loading cue).
            ->spa()
            // Collapsible left sidebar on desktop.
            ->sidebarCollapsibleOnDesktop()
            // Brand primary derived from the logo colour (#2d19ec).
            ->colors([
                'primary' => Color::hex('#2d19ec'),
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Pillar-based navigation, mirroring Localith's IA. Dashboard stays
            // ungrouped at the top; remaining pillars (Posts, Reports, SEO) are
            // added as those features land.
            ->navigationGroups([
                NavigationGroup::make('Listings')->label(fn (): string => __('nav.group_listings')),
                NavigationGroup::make('Reviews')->label(fn (): string => __('nav.group_reviews')),
                NavigationGroup::make('Reports')->label(fn (): string => __('nav.group_reports')),
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
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => view('partials.favicons')->render(),
            )
            // Button colour overrides.
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => view('partials.panel-styles')->render(),
            )
            // Subscription paywall: blocks the app when there's no active plan
            // or a payment problem (after the grace window). See SubscriptionGate.
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => view('filament.billing-gate')->render(),
            )
            // Global search is disabled; the workspace switcher takes its place
            // on the right of the top bar, left of the user avatar menu.
            ->globalSearch(false)
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.app.workspace-switcher')->render(),
            )
            // Language switcher + legal links under the auth (login/register) card.
            ->renderHook(
                \Filament\View\PanelsRenderHook::SIMPLE_PAGE_END,
                fn (): string => view('partials.auth-footer')->render(),
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
                \App\Http\Middleware\SetLocale::class,
                SetCurrentWorkspace::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\ApplyUserPreferences::class,
                \App\Http\Middleware\MarkOnboardingComplete::class,
            ]);
    }
}
