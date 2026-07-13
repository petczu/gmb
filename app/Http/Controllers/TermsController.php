<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The "Terms changed — review and accept" interstitial the app redirects to
 * (see EnsureTermsAccepted) after a new Terms version is published.
 */
class TermsController extends Controller
{
    public function review(Request $request)
    {
        $current = LegalDocument::currentVersion(LegalDocument::TERMS);

        // Nothing pending (raced with an accept in another tab) → back to the app.
        if ($current === 0 || (int) $request->user()?->getAttribute('terms_version') >= $current) {
            return redirect('/');
        }

        $locale = in_array($request->user()?->getAttribute('locale'), ['en', 'de'], true)
            ? $request->user()->getAttribute('locale')
            : app()->getLocale();
        app()->setLocale(in_array($locale, ['en', 'de'], true) ? $locale : 'en');

        $body = LegalDocument::bodyFor(LegalDocument::TERMS, app()->getLocale()) ?? '';

        return view('legal.accept', [
            'html' => new HtmlString(Str::markdown($body)),
            'version' => $current,
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $request->user()?->forceFill([
            'terms_version' => LegalDocument::currentVersion(LegalDocument::TERMS),
            'terms_accepted_at' => now(),
        ])->save();

        return redirect('/');
    }
}
