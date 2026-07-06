<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PostmarkWebhookController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewPageController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSwitchController;
use App\Http\Controllers\ZernioConnectController;
use App\Http\Controllers\ZernioWebhookController;
use App\Http\Middleware\SetCurrentWorkspace;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

// Switch the active workspace (multi-workspace members). Writes the session
// pointer; SetCurrentWorkspace initializes the new tenant on the next request.
Route::middleware(['web', 'auth'])
    ->post('workspace/switch', WorkspaceSwitchController::class)
    ->name('workspace.switch');

// Create an additional workspace from the switcher.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('workspace/new', [WorkspaceController::class, 'create'])->name('workspace.create');
    Route::post('workspace/new', [WorkspaceController::class, 'store'])->name('workspace.store');
});

// Zernio Google Business OAuth connect flow (browser redirect + callback).
Route::middleware(['web', 'auth', SetCurrentWorkspace::class])
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
        if (in_array($locale, ['en', 'de'], true)) {
            session(['locale' => $locale]);
        }

        return redirect(url()->previous('/'));
    })->name('locale.switch');

    Route::view('terms', 'legal.page', ['page' => 'terms'])->name('legal.terms');
    Route::view('privacy', 'legal.page', ['page' => 'privacy'])->name('legal.privacy');
    Route::view('cookies', 'legal.page', ['page' => 'cookies'])->name('legal.cookies');
});
