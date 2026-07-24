<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PostmarkWebhookController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewPageController;
use App\Http\Controllers\ReviewWidgetController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSwitchController;
use App\Http\Controllers\ZernioConnectController;
use App\Http\Controllers\ZernioWebhookController;
use App\Http\Middleware\EnsureBetaApproved;
use App\Http\Middleware\SetCurrentWorkspace;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\Locales;
use App\Support\MarketingSite;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

// The app panel is mounted at the root path (see AppPanelProvider), so "/" is
// the dashboard (redirects to login when signed out). No separate landing route.

// Stripe webhook (subscription status, payment failures). No tenant context —
// Cashier resolves the billable Workspace by stripe_id on the central DB.
Route::post('stripe/webhook', [CashierWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Postmark webhook (bounces + spam complaints → suppression list). The {secret}
// path segment authenticates it. No CSRF (see bootstrap/app.php).
Route::post('postmark/webhook/{secret}', [PostmarkWebhookController::class, 'handle'])
    ->name('postmark.webhook');

// Zernio webhook (new reviews + reply reconciliation + account status). The raw
// body is HMAC-SHA256 verified in the controller. No CSRF (see bootstrap/app.php).
Route::post('zernio/webhook', [ZernioWebhookController::class, 'handle'])
    ->name('zernio.webhook');

// Performance report preview (iframe) + PDF download. Tenant-scoped.
Route::middleware(['web', 'auth', SetCurrentWorkspace::class])
    ->prefix('reports')
    ->group(function () {
        Route::get('/preview', [ReportController::class, 'preview'])->name('reports.preview');
        Route::get('/download', [ReportController::class, 'download'])->name('reports.download');
        Route::get('/saved/{id}/preview', [ReportController::class, 'savedPreview'])->name('reports.saved.preview');
        Route::get('/saved/{id}/download', [ReportController::class, 'savedDownload'])->name('reports.saved.download');
    });

// Download a Stripe invoice PDF for the current workspace (Billing → Invoices).
Route::middleware(['web', 'auth', SetCurrentWorkspace::class])
    ->get('billing/invoice/{invoiceId}', [BillingController::class, 'invoice'])
    ->name('billing.invoice');

// PUBLIC shared report links (no login). The HTML is stored on the share row,
// so no tenant context is needed. Optional password + access window enforced.
Route::middleware('web')
    ->prefix('reports/shared')
    ->group(function () {
        Route::get('/{token}', [ReportController::class, 'shared'])->name('reports.shared');
        Route::post('/{token}', [ReportController::class, 'sharedUnlock'])->name('reports.shared.unlock');
    });

// PUBLIC workspace invitation accept flow (no tenant context — the invitee may
// not have a workspace yet). The token authenticates the link.
Route::middleware('web')->group(function () {
    Route::get('invite/{token}', [InvitationController::class, 'show'])->name('invite.show');
    Route::post('invite/{token}', [InvitationController::class, 'accept'])->name('invite.accept');
});

// Private beta: "request received" screen for signed-in users whose account
// has not been activated yet (EnsureBetaApproved redirects them here).
Route::middleware(['web', 'auth', SetLocale::class])
    ->get('beta/pending', function () {
        $user = auth()->user();

        if ($user instanceof User && $user->hasBetaAccess()) {
            return redirect('/');
        }

        return response()->view('beta.pending', ['email' => (string) $user?->email]);
    })
    ->name('beta.pending');

// Switch the active workspace (multi-workspace members). Writes the session
// pointer; SetCurrentWorkspace initializes the new tenant on the next request.
Route::middleware(['web', 'auth'])
    ->post('workspace/switch', WorkspaceSwitchController::class)
    ->name('workspace.switch');

// Create an additional workspace from the switcher. Beta-gated so pending
// applicants can't provision tenant databases through this side door.
Route::middleware(['web', 'auth', EnsureBetaApproved::class])->group(function (): void {
    Route::get('workspace/new', [WorkspaceController::class, 'create'])->name('workspace.create');
    Route::post('workspace/new', [WorkspaceController::class, 'store'])->name('workspace.store');
});

// Zernio Google Business OAuth connect flow (browser redirect + callback).
Route::middleware(['web', 'auth', EnsureBetaApproved::class, SetCurrentWorkspace::class])
    ->prefix('connect/google')
    ->group(function () {
        Route::get('/', [ZernioConnectController::class, 'connect'])->name('zernio.google.connect');
        Route::get('/callback', [ZernioConnectController::class, 'callback'])->name('zernio.google.callback');
    });

// PUBLIC review-collection pages ("leave a review" funnels). Central data, no
// tenancy. Custom domains are handled by ServeReviewPageDomain (bootstrap).
Route::middleware('web')->group(function (): void {
    Route::get('r/{slug}', [ReviewPageController::class, 'show'])->name('review-page.show');
    Route::get('r/{slug}/go/{target}', [ReviewPageController::class, 'go'])->name('review-page.go');
});

// PUBLIC review-showcase widgets, embedded on the customer's own site. Central
// snapshot, no tenancy. Session/cookie middleware is stripped so the responses
// stay cacheable and set no third-party cookies.
Route::withoutMiddleware([
    StartSession::class,
    AddQueuedCookiesToResponse::class,
    ShareErrorsFromSession::class,
    // The CSRF middleware reads the session (to set the XSRF cookie) even on
    // GET, so it must go too or it throws "Session store not set".
    PreventRequestForgery::class,
    EncryptCookies::class,
])->group(function (): void {
    // Pretty, uniform loader: the widget id travels in the query string.
    Route::get('widget.js', [ReviewWidgetController::class, 'loader'])->name('review-widget.loader');
    // Path forms (kept for already-embedded snippets).
    Route::get('w/{token}.js', [ReviewWidgetController::class, 'js'])
        ->where('token', '[a-z0-9]+')->name('review-widget.js');
    Route::get('w/{token}', [ReviewWidgetController::class, 'embed'])
        ->where('token', '[a-z0-9]+')->name('review-widget.embed');
});

// Public developer documentation (markdown pages + Scalar API reference).
Route::prefix('docs')->group(function (): void {
    Route::get('/', [DocsController::class, 'show'])->name('docs.index');
    Route::get('/api-reference', [DocsController::class, 'apiReference'])->name('docs.api-reference');
    Route::get('/changelog', [DocsController::class, 'changelog'])->name('docs.changelog');
    Route::get('/{slug}', [DocsController::class, 'show'])
        ->where('slug', '[a-z0-9/-]+')
        ->name('docs.show');
});

// One-click unsubscribe from the onboarding/product email series. Signed URL
// (from the emails), no login required; the profile toggle re-enables it.
Route::middleware('web')->get('unsubscribe/product/{user}', function (User $user) {
    abort_unless(request()->hasValidSignature(), 403);

    $user->forceFill(['product_emails' => false])->save();

    return view('unsubscribed');
})->name('unsubscribe.product');

// Language switcher (persists the visitor's choice) + public legal pages.
Route::middleware(['web', SetLocale::class])->group(function (): void {
    Route::get('locale/{locale}', function (string $locale) {
        if (in_array($locale, Locales::codes(), true)) {
            session(['locale' => $locale]);

            // Persist to the signed-in user so SetLocale (which prefers the
            // stored locale) reflects the choice everywhere, including the beta
            // pending screen and future emails.
            if (($user = auth()->user()) instanceof User) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }

        return redirect(url()->previous('/'));
    })->name('locale.switch');

    // The legal pages moved to the marketing site — keep the old app URLs
    // alive as redirects (bookmarks, old emails). The in-app acceptance flows
    // (registration scroll box, /terms/review) keep rendering the DB copy.
    Route::get('terms', fn () => redirect(MarketingSite::legal('terms'), 301))->name('legal.terms');
    Route::get('privacy', fn () => redirect(MarketingSite::legal('privacy'), 301))->name('legal.privacy');
    Route::get('cookies', fn () => redirect(MarketingSite::legal('cookies'), 301))->name('legal.cookies');
});

// Terms re-acceptance interstitial: EnsureTermsAccepted redirects here after a
// new Terms version is published; accepting stamps the user and unblocks the app.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('terms/review', [TermsController::class, 'review'])->name('terms.review');
    Route::post('terms/accept', [TermsController::class, 'accept'])->name('terms.accept');
});

// Local-only previews of the error pages (in production they render on real errors).
if (app()->environment('local')) {
    Route::get('/dev/errors/{code}', fn (string $code) => in_array($code, ['401', '403', '404', '500'], true)
        ? response()->view("errors.{$code}", [], (int) $code)
        : abort(404));
}
