<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReviewPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PUBLIC review-collection pages: /r/{slug} (or a connected custom domain).
 * No auth, no tenancy — the page row is central and self-contained.
 */
class ReviewPageController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $page = ReviewPage::query()->where('slug', $slug)->where('active', true)->firstOrFail();

        return $this->render($request, $page);
    }

    /** Redirect to the chosen platform and count the click. */
    public function go(Request $request, string $slug, string $target): RedirectResponse
    {
        $page = ReviewPage::query()->where('slug', $slug)->where('active', true)->firstOrFail();

        foreach ($page->targets() as $t) {
            if (($t['key'] ?? '') === $target) {
                $page->bump($target);

                return redirect()->away($t['url']);
            }
        }

        abort(404);
    }

    /** Shared renderer, also used by the custom-domain middleware. */
    public function render(Request $request, ReviewPage $page): View
    {
        $languages = (array) ($page->settings['languages'] ?? ['en']);
        $lang = $request->query('lang');

        if (! in_array($lang, $languages, true)) {
            $lang = $languages[0] ?? 'en';
        }

        // A view is a human page load; previews from the configurator are not counted.
        if (! $request->boolean('preview')) {
            $page->bump('view');
        }

        return view('review-page.show', [
            'page' => $page,
            'lang' => $lang,
            'languages' => $languages,
        ]);
    }
}
