<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * DataForSEO Business Data client, used for the nightly competitor snapshots:
 * one "My Business Info" live lookup per place is a fraction of the price of
 * a Google Place Details call. Interactive search stays on the Places API.
 */
class DataForSeoClient
{
    protected const BASE = 'https://api.dataforseo.com/v3';

    public function configured(): bool
    {
        return filled(config('services.dataforseo.login'))
            && filled(config('services.dataforseo.password'));
    }

    /**
     * Current rating snapshot of a Google place, shaped exactly like
     * PlacesClient::details() so the refresh command can use either. Unlike
     * Places, DataForSEO also returns the 1-5 star distribution.
     *
     * @return array{place_id: string, name: ?string, address: ?string, rating: ?float, reviews_count: int, rating_distribution: ?array<int, int>}
     */
    public function details(string $placeId): array
    {
        $response = $this->request()
            ->post(self::BASE.'/business_data/google/my_business_info/live', [[
                'keyword' => 'place_id:'.$placeId,
                // A location + language are required by the API but do not
                // constrain a place_id lookup; EN/US keeps responses uniform.
                'location_code' => 2840,
                'language_code' => 'en',
            ]])
            ->throw()
            ->json();

        $task = $response['tasks'][0] ?? [];

        // DataForSEO wraps per-task errors in a 20000-family status code.
        if ((int) ($task['status_code'] ?? 0) !== 20000) {
            throw new \RuntimeException(sprintf(
                'DataForSEO task failed (%s): %s',
                $task['status_code'] ?? 'no status',
                $task['status_message'] ?? 'no message',
            ));
        }

        $item = $task['result'][0]['items'][0] ?? null;

        if (! is_array($item)) {
            throw new \RuntimeException('DataForSEO returned no business item for '.$placeId);
        }

        // {1..5 => count}, integer-keyed and only kept when it looks valid.
        $distribution = null;
        if (is_array($item['rating_distribution'] ?? null)) {
            $distribution = collect($item['rating_distribution'])
                ->mapWithKeys(fn ($count, $star): array => [(int) $star => (int) $count])
                ->filter(fn ($count, $star): bool => $star >= 1 && $star <= 5)
                ->all();
            $distribution = $distribution === [] ? null : $distribution;
        }

        return [
            'place_id' => (string) ($item['place_id'] ?? $placeId),
            'name' => $item['title'] ?? null,
            'address' => $item['address'] ?? null,
            'rating' => isset($item['rating']['value']) ? (float) $item['rating']['value'] : null,
            'reviews_count' => (int) ($item['rating']['votes_count'] ?? 0),
            'rating_distribution' => $distribution,
        ];
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth(
            (string) config('services.dataforseo.login'),
            (string) config('services.dataforseo.password'),
        )
            ->acceptJson()
            ->timeout(30)
            ->connectTimeout(5);
    }
}
