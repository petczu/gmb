<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\ReviewPageController;
use App\Models\ReviewPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a review-collection page on its connected CUSTOM DOMAIN
 * (e.g. review.gameover-vienna.at → this app). Short-circuits before routing:
 * when the request host is not the app's own host and matches a page's
 * custom_domain, the page is rendered directly. The owner points a CNAME at
 * the app domain; TLS termination is the hosting layer's job.
 */
class ServeReviewPageDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = strtolower($request->getHost());

        if ($host !== '' && $host !== $appHost) {
            $page = ReviewPage::findByDomain($host);

            if ($page !== null) {
                // The whole domain belongs to the page: any path renders it,
                // except the click-through path which still tracks + redirects.
                if (preg_match('#^/go/([a-z0-9_-]+)$#', $request->getPathInfo(), $m)) {
                    return app(ReviewPageController::class)->go($request, $page->slug, $m[1])->toResponse($request);
                }

                return response(app(ReviewPageController::class)->render($request, $page));
            }
        }

        return $next($request);
    }
}
