<?php

namespace App\Providers;

use App\Billing\Plans;
use App\Http\Middleware\SetCurrentWorkspace;
use App\Listeners\GrantCreditPack;
use App\Listeners\SendBillingEmails;
use App\Livewire\ScopedDatabaseNotifications;
use App\Models\CashierSubscription;
use App\Models\CashierSubscriptionItem;
use App\Models\EmailSuppression;
use App\Models\McpWorkspaceSelection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\ClaudeReplyGenerator;
use App\Services\Ai\FakeReplyGenerator;
use App\Services\Ai\ReplyGenerator;
use App\Services\Billing\LocationBilling;
use App\Services\Reviews\FakeReviewProvider;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use App\Services\Reviews\ZernioProvider;
use App\Support\FavoritePages;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Zernio\Configuration;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The Socialite login route (/oauth/{provider}) registers before
        // Passport's /oauth/authorize and would swallow it, breaking the MCP
        // OAuth flow (ProviderNotConfigured "authorize"). Constrain {provider}
        // to the social providers so Passport routes match again. Must run in
        // register(): patterns only apply to routes registered afterwards, and
        // package routes load before this provider's boot().
        Route::pattern('provider', 'google|linkedin-openid|microsoft');

        $this->app->singleton(ReviewProviderFactory::class);

        // Default (token-less) resolution for generic injection. Per-workspace
        // instances (with the Zernio token) come from ReviewProviderFactory.
        $this->app->bind(ReviewProvider::class, function () {
            return config('services.reviews.driver') === 'zernio'
                ? new ZernioProvider(null)
                : new FakeReviewProvider;
        });

        $this->app->bind(ReplyGenerator::class, function () {
            // Anything other than 'fake' uses the real Anthropic generator.
            return config('services.ai.driver') === 'fake'
                ? new FakeReplyGenerator
                : new ClaudeReplyGenerator;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The Zernio PHP SDK serializes boolean query params as 0/1 by default,
        // but Zernio only accepts the string literals true/false (e.g.
        // includeOverLimit=0 → 400 "includeOverLimit is invalid"). Flip the
        // default once so every SDK client (they all share this configuration)
        // sends valid booleans.
        Configuration::getDefaultConfiguration()
            ->setBooleanFormatForQueryString(Configuration::BOOLEAN_FORMAT_STRING);

        // MCP OAuth (Passport) consent screen shown to the AI client connecting
        // over /mcp. Besides approving access, it lets a user who belongs to
        // several Pro workspaces pick which one this connection is scoped to;
        // the choice is bound in McpAuthorizationController and read back by
        // ResolveMcpWorkspace.
        Passport::authorizationView(function (array $parameters) {
            $user = $parameters['user'];
            $billing = app(LocationBilling::class);

            $workspaces = $user->workspaces()->get()
                ->filter(fn (Workspace $workspace): bool => $billing->allows($workspace, Plans::MCP))
                ->values();

            $bound = McpWorkspaceSelection::query()
                ->where('user_id', $user->getKey())
                ->where('oauth_client_id', $parameters['client']->getKey())
                ->value('workspace_id');

            return view('mcp.authorize', [
                ...$parameters,
                'workspaces' => $workspaces,
                'selectedWorkspaceId' => $bound ?? $workspaces->first()?->getKey(),
            ]);
        });

        // Scope the notifications bell to the active workspace. Overrides
        // Filament's 'database-notifications' alias with our workspace-aware
        // component (registered after Filament's own boot).
        $this->app->booted(function (): void {
            Livewire::component('database-notifications', ScopedDatabaseNotifications::class);
        });

        // Livewire temp uploads must use a CENTRAL disk — stancl suffixes the
        // local/public disks per tenant, which breaks file uploads inside the
        // app panel (stream_copy_to_stream null source).
        config(['livewire.temporary_file_upload.disk' => 'livewire-tmp']);

        // Hide the pagination footer when everything fits on one page (≤10 rows).
        Table::configureUsing(function (Table $table): void {
            $table->paginated(fn (HasTable $livewire): bool => $livewire->getFilteredTableQuery()->count() > 10);
        });

        // Consistent date/time fields site-wide, each with one leading
        // calendar/clock icon that opens the picker (see render hook below).
        // Date fields use the JS calendar widget; TIME fields stay native —
        // the browser time input types far better than the JS dropdown (a
        // "15 : 3" spinner that fights manual entry). The native right-side
        // indicator is hidden by CSS so the leading icon is the only one.
        // A per-field ->prefixIcon()/->native() still overrides.
        DateTimePicker::configureUsing(fn (DateTimePicker $picker) => $picker->native(false)->prefixIcon(Heroicon::OutlinedCalendar));
        DatePicker::configureUsing(fn (DatePicker $picker) => $picker->native(false)->prefixIcon(Heroicon::OutlinedCalendar));
        TimePicker::configureUsing(fn (TimePicker $picker) => $picker->prefixIcon(Heroicon::OutlinedClock));

        // The picker's prefix icon sits on the outer field wrapper, outside the
        // clickable trigger, so clicking it did nothing. Forward icon clicks to
        // the JS picker's trigger — or, for native date/time inputs, call
        // showPicker(). Delegated, so it also covers fields inside modals.
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .fi-fo-date-time-picker .fi-input-wrp-prefix,
                    .fi-fo-date-time-picker .fi-input-wrp-prefix svg { cursor: pointer; }
                    /* One icon only: the leading one opens the native picker. */
                    .fi-fo-date-time-picker input::-webkit-calendar-picker-indicator { display: none; }
                </style>
                <script>
                    document.addEventListener('click', function (event) {
                        const picker = event.target.closest('.fi-fo-date-time-picker');
                        if (! picker) { return; }
                        if (event.target.closest('.fi-fo-date-time-picker-trigger, input')) { return; }
                        const native = picker.querySelector('input[type="time"], input[type="date"], input[type="datetime-local"]');
                        if (native) {
                            native.focus();
                            try { native.showPicker(); } catch (e) { /* needs a user gesture or unsupported */ }
                            return;
                        }
                        const trigger = picker.querySelector('.fi-fo-date-time-picker-trigger, .fi-fo-date-time-picker-display-text-input');
                        if (trigger) { trigger.focus(); trigger.click(); }
                    });
                </script>
                HTML),
        );

        // Cashier: the Workspace (stancl tenant) is the billable; subscription
        // models are pinned to the central connection.
        Cashier::useCustomerModel(Workspace::class);
        Cashier::useSubscriptionModel(CashierSubscription::class);
        Cashier::useSubscriptionItemModel(CashierSubscriptionItem::class);

        // Microsoft OAuth login (socialiteproviders/microsoft driver).
        Event::listen(
            SocialiteWasCalled::class,
            MicrosoftExtendSocialite::class,
        );

        // Billing emails (receipt / payment-failed) from Stripe webhooks.
        Event::listen(
            WebhookReceived::class,
            SendBillingEmails::class,
        );

        // Grant purchased AI-reply top-up packs from the checkout webhook.
        Event::listen(
            WebhookReceived::class,
            GrantCreditPack::class,
        );

        // Never email an address on the suppression list (bounced / complained).
        // Returning false from a MessageSending listener cancels the send.
        Event::listen(
            MessageSending::class,
            function (MessageSending $event): ?bool {
                foreach ($event->message->getTo() as $address) {
                    if (EmailSuppression::isSuppressed($address->getAddress())) {
                        return false;
                    }
                }

                return null;
            },
        );

        // The Owner role can always do everything in its workspace — its
        // permissions are implicit, not editable. (Scoped to the current team
        // by SetCurrentWorkspace.) Return null for non-owners so other
        // permission checks still run.
        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole('owner') ? true : null);

        // The signed-in user's starred pages as extra sidebar items. Must run
        // per request (serving), because the list differs per user.
        Filament::serving(function (): void {
            $panel = Filament::getCurrentPanel();
            $user = auth()->user();

            if ($panel?->getId() !== 'app' || ! $user instanceof User) {
                return;
            }

            $items = FavoritePages::navigationItems($user);

            if ($items !== []) {
                $panel->navigationItems($items);
            }
        });

        // Keep the current workspace (tenant) initialized across Livewire AJAX
        // updates. Without this, modals/typing/drag in the `app` panel lose the
        // tenant and queries fall back to the central DB. See gmb-gotchas.
        Livewire::addPersistentMiddleware([
            SetCurrentWorkspace::class,
        ]);

        // Suppress the browser's native "Please fill out this field" bubbles so
        // Filament's own inline validation (styled messages under each field)
        // shows instead. Marks every form `novalidate`, re-applied across SPA
        // navigations and Livewire DOM updates (modals, dynamically added forms).
        // Global (via FilamentView) so it covers both the app and admin panels.
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                    (function () {
                        const apply = () => document.querySelectorAll('form:not([novalidate])')
                            .forEach((form) => form.setAttribute('novalidate', 'novalidate'));
                        let queued = false;
                        const schedule = () => {
                            if (queued) { return; }
                            queued = true;
                            requestAnimationFrame(() => { queued = false; apply(); });
                        };
                        apply();
                        document.addEventListener('livewire:navigated', apply);
                        new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
                    })();
                </script>
                HTML),
        );

        // Live connection indicator (offline / back-online toast). Global so it
        // covers both panels; the view is self-contained (Alpine + scoped CSS).
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): HtmlString => new HtmlString(view('filament.connection-status')->render()),
        );
    }
}
