<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    /**
     * Stream a Stripe invoice PDF for the current workspace. Cashier validates
     * that the invoice belongs to this customer, so a mismatched id 404s.
     */
    public function invoice(Request $request, string $invoiceId): Response
    {
        abort_unless($request->user()?->can('manage_billing') ?? false, 403);

        $workspace = Workspace::findOrFail((string) session('current_workspace_id'));
        abort_if($workspace->stripe_id === null, 404);

        try {
            return $workspace->downloadInvoice($invoiceId, [
                'vendor' => 'Repunio',
                'product' => __('pages/billing.invoice_product'),
            ]);
        } catch (\Throwable) {
            abort(404);
        }
    }
}
