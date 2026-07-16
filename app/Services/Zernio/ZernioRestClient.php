<?php

declare(strict_types=1);

namespace App\Services\Zernio;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Client for Zernio's native REST API (https://zernio.com/openapi.yaml):
 * post publishing + GBP location details. Uses the SAME base URL and API key
 * as the review sync (ZERNIO_BASE_URL + ZERNIO_API_KEY, Bearer auth) — no
 * extra credentials needed.
 */
class ZernioRestClient
{
    public function configured(): bool
    {
        return filled(config('services.reviews.zernio_key'))
            && filled($this->baseUrl());
    }

    /**
     * Create (and publish or schedule) a post. Returns the created post.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function createPost(array $payload): array
    {
        $response = $this->request()
            ->withHeaders(['x-request-id' => (string) Str::uuid()])
            ->post('/posts', $payload)
            ->throw()
            ->json();

        // The API may wrap the post ({post: {...}}) or return it directly.
        return (array) ($response['post'] ?? $response);
    }

    /**
     * List a social account's external (natively-published) posts, paginated.
     * source=external returns posts published directly on the platform, not
     * through Zernio (e.g. Google Business localPosts created in the Google UI).
     *
     * @return array{posts: array<int, array<string, mixed>>, pagination: array<string, mixed>}
     *
     * @throws RequestException
     */
    public function listExternalPosts(string $accountId, int $page = 1, int $limit = 100): array
    {
        $response = (array) $this->request()
            ->get('/posts', [
                'source' => 'external',
                'accountId' => $accountId,
                'page' => $page,
                'limit' => $limit,
            ])
            ->throw()
            ->json();

        return [
            'posts' => (array) ($response['posts'] ?? []),
            'pagination' => (array) ($response['pagination'] ?? []),
        ];
    }

    /**
     * Trigger an on-demand sync of an account's latest external posts so a
     * freshly published post is retrievable within seconds instead of waiting
     * for the background sync (which refreshes each account every ~90 minutes).
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function syncExternalPosts(string $accountId): array
    {
        return (array) $this->request()
            ->post('/posts/sync-external', ['accountId' => $accountId])
            ->throw()
            ->json();
    }

    /**
     * GBP location details (hours, description, phone, website, status).
     *
     * @return array<string, mixed>
     */
    public function locationDetails(string $accountId, ?string $locationId = null, string $readMask = 'title,phoneNumbers,websiteUri,regularHours,specialHours,profile'): array
    {
        return (array) $this->request()
            ->get(sprintf('/accounts/%s/gmb-location-details', $accountId), array_filter([
                'locationId' => $locationId,
                'readMask' => $readMask,
            ]))
            ->throw()
            ->json();
    }

    /**
     * GBP performance metrics (impressions, calls, clicks, directions,
     * bookings) as daily series with totals. Dates are YYYY-MM-DD; Google
     * provides at most 18 months of history and the last ~5 days lag.
     *
     * @return array<string, mixed> ['metrics' => [NAME => ['total' => int, 'values' => [['date','value'], ...]]]]
     */
    public function performance(string $accountId, string $startDate, string $endDate, ?string $metrics = null): array
    {
        return (array) $this->request()
            ->get('/analytics/googlebusiness/performance', array_filter([
                'accountId' => $accountId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'metrics' => $metrics,
            ]))
            ->throw()
            ->json();
    }

    /**
     * GBP search keywords with impressions. Months are YYYY-MM (defaults to
     * the last 3 months on the API side).
     *
     * @return array<int, array{keyword: string, impressions: int}>
     */
    public function searchKeywords(string $accountId, ?string $startMonth = null, ?string $endMonth = null): array
    {
        $response = (array) $this->request()
            ->get('/analytics/googlebusiness/search-keywords', array_filter([
                'accountId' => $accountId,
                'startMonth' => $startMonth,
                'endMonth' => $endMonth,
            ]))
            ->throw()
            ->json();

        return (array) ($response['keywords'] ?? []);
    }

    /**
     * Currently set Google Business attributes (e.g. the url_* social links).
     *
     * @return array<int, array<string, mixed>>
     */
    public function attributes(string $accountId, string $locationId): array
    {
        $response = (array) $this->request()
            ->get(sprintf('/accounts/%s/gmb-attributes', $accountId), ['locationId' => $locationId])
            ->throw()
            ->json();

        return (array) ($response['attributes'] ?? []);
    }

    /**
     * PUT Google Business attributes (full values per attribute).
     *
     * @param  array<int, array{name: string, values: array<int, mixed>}>  $attributes
     */
    public function updateAttributes(string $accountId, string $locationId, array $attributes): void
    {
        $this->request()
            ->put(
                sprintf('/accounts/%s/gmb-attributes', $accountId).'?locationId='.urlencode($locationId),
                ['attributes' => $attributes],
            )
            ->throw();
    }

    /**
     * PATCH GBP location details. $payload must contain updateMask plus the
     * fields being changed (proxies Google's locations.patch).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateLocationDetails(string $accountId, ?string $locationId, array $payload): array
    {
        return (array) $this->request()
            ->patch(
                sprintf('/accounts/%s/gmb-location-details', $accountId)
                    .($locationId !== null ? '?locationId='.urlencode($locationId) : ''),
                $payload,
            )
            ->throw()
            ->json();
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.reviews.zernio_base_url', 'https://zernio.com/api/v1'), '/');
    }

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.reviews.zernio_key'))
            ->acceptJson()
            ->timeout(30)
            ->connectTimeout(5);
    }
}
