<?php

namespace App\Providers;

use App\Http\Middleware\SetCurrentWorkspace;
use App\Services\Ai\ClaudeReplyGenerator;
use App\Services\Ai\FakeReplyGenerator;
use App\Services\Ai\ReplyGenerator;
use App\Services\Reviews\FakeReviewProvider;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use App\Services\Reviews\ZernioProvider;
use App\Models\CashierSubscription;
use App\Models\CashierSubscriptionItem;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Passport;
use Livewire\Livewire;

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
                : new FakeReviewProvider();
        });

        $this->app->bind(ReplyGenerator::class, function () {
            // Anything other than 'fake' uses the real Anthropic generator.
            return config('services.ai.driver') === 'fake'
                ? new FakeReplyGenerator()
                : new ClaudeReplyGenerator();
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
        \Filament\Tables\Table::configureUsing(function (\Filament\Tables\Table $table): void {
            $table->paginated(fn (\Filament\Tables\Contracts\HasTable $livewire): bool => $livewire->getFilteredTableQuery()->count() > 10);
        });

        // Cashier: the Workspace (stancl tenant) is the billable; subscription
        // models are pinned to the central connection.
        Cashier::useCustomerModel(Workspace::class);
        Cashier::useSubscriptionModel(CashierSubscription::class);
        Cashier::useSubscriptionItemModel(CashierSubscriptionItem::class);

        // Billing emails (receipt / payment-failed) from Stripe webhooks.
        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \App\Listeners\SendBillingEmails::class,
        );

        // Grant purchased AI-reply top-up packs from the checkout webhook.
        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            \App\Listeners\GrantCreditPack::class,
        );

        // Never email an address on the suppression list (bounced / complained).
        // Returning false from a MessageSending listener cancels the send.
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Mail\Events\MessageSending::class,
            function (\Illuminate\Mail\Events\MessageSending $event): ?bool {
                foreach ($event->message->getTo() as $address) {
                    if (\App\Models\EmailSuppression::isSuppressed($address->getAddress())) {
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

        // Keep the current workspace (tenant) initialized across Livewire AJAX
        // updates. Without this, modals/typing/drag in the `app` panel lose the
        // tenant and queries fall back to the central DB. See gmb-gotchas.
        Livewire::addPersistentMiddleware([
            SetCurrentWorkspace::class,
        ]);
    }
}
