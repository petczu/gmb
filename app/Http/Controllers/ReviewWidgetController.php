<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReviewWidget;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * PUBLIC review-showcase widgets. Served cross-origin from the CENTRAL row and
 * its pre-built snapshot, with no auth or tenancy:
 *   /w/{token}.js  a loader that injects the widget into the customer's page
 *   /w/{token}     a full self-contained HTML page (iframe fallback + preview)
 */
class ReviewWidgetController extends Controller
{
    /** Pretty loader alias: /widget.js?id={token} → same as /w/{token}.js. */
    public function loader(Request $request): Response
    {
        return $this->js($request, (string) $request->query('id', ''));
    }

    /** The `<script>` embed: injects styles + markup into the mount div. */
    public function js(Request $request, string $token): Response
    {
        $widget = ReviewWidget::query()->where('token', $token)->where('active', true)->first();

        if ($widget === null) {
            return response('/* widget not found */', 404)
                ->header('Content-Type', 'application/javascript');
        }

        $html = $this->renderMarkup($widget);
        $mountId = 'reviews-widget-'.$widget->token;

        // Inline the rendered markup as a string and drop it into the mount the
        // customer placed. Inline onclick handlers (slider nav) survive innerHTML,
        // so the widget needs no further JS wiring.
        $js = '(function(){var id='.json_encode($mountId).';var m=document.getElementById(id);'
            .'if(!m){m=document.querySelector("[data-reviews-widget=\''.$widget->token.'\']");}'
            .'if(!m){return;}m.innerHTML='.json_encode($html).';})();';

        return response($js)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=300');
    }

    /** Full-page HTML: used by the builder preview iframe and as a fallback embed. */
    public function embed(Request $request, string $token): View|Response
    {
        $widget = ReviewWidget::query()->where('token', $token)->where('active', true)->first();

        if ($widget === null) {
            abort(404);
        }

        return response()->view('widgets.page', [
            'widget' => $widget,
            'markup' => $this->renderMarkup($widget),
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /** Render the shared style + markup fragment (no surrounding document). */
    private function renderMarkup(ReviewWidget $widget): string
    {
        return view('widgets.embed', [
            'widget' => $widget,
            'jsonLd' => $this->jsonLd($widget),
        ])->render();
    }

    /**
     * schema.org AggregateRating + Review data so the reviews can surface as
     * rich results when the widget is server-rendered (iframe/page variant).
     *
     * @return array<string, mixed>
     */
    private function jsonLd(ReviewWidget $widget): array
    {
        $summary = $widget->snapshotSummary();
        $name = $widget->setting('header_title') ?: ($widget->workspace?->name ?? 'Business');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $name,
        ];

        if (($summary['count'] ?? 0) > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $summary['average'],
                'reviewCount' => (string) $summary['count'],
            ];
        }

        $data['review'] = array_map(fn (array $r): array => [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $r['author'] ?: 'Anonymous'],
            'datePublished' => $r['date_iso'],
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string) $r['rating'], 'bestRating' => '5'],
            'reviewBody' => $r['text'],
        ], $widget->snapshotReviews());

        return $data;
    }
}
