<?php

namespace App\Providers;

use App\Http\Middleware\SetCurrentWorkspace;
use App\Listeners\GrantCreditPack;
use App\Listeners\SendBillingEmails;
use App\Models\CashierSubscription;
use App\Models\CashierSubscriptionItem;
use App\Models\EmailSuppression;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\ClaudeReplyGenerator;
use App\Services\Ai\FakeReplyGenerator;
use App\Services\Ai\ReplyGenerator;
use App\Services\Reviews\FakeReviewProvider;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use App\Services\Reviews\ZernioProvider;
use App\Support\FavoritePages;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        // MCP OAuth (Passport) consent screen shown to the AI client connecting
        // over /mcp/{workspace}. Uses the published mcp.authorize view.
        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters));

        // Livewire temp uploads must use a CENTRAL disk — stancl suffixes the
        // local/public disks per tenant, which breaks file uploads inside the
        // app panel (stream_copy_to_stream null source).
        config(['livewire.temporary_file_upload.disk' => 'livewire-tmp']);

        // Hide the pagination footer when everything fits on one page (≤10 rows).
        Table::configureUsing(function (Table $table): void {
            $table->paginated(fn (HasTable $livewire): bool => $livewire->getFilteredTableQuery()->count() > 10);
        });

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
    }
}
