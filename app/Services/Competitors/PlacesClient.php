<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Google Places API (New) client: text search + place details, only
 * the fields needed for competitor benchmarking (rating + review count).
 */
class PlacesClient
{
    protected const BASE = 'https://places.googleapis.com/v1';

    protected const FIELDS = 'id,displayName,formattedAddress,rating,userRatingCount';

    /**
     * Interactive search only shows name + address; leaving out the rating
     * fields keeps those calls in the cheaper Basic SKU (bigger free tier).
     */
    protected const SEARCH_FIELDS = 'id,displayName,formattedAddress';

    public function configured(): bool
    {
        return filled(config('services.google.places_key'));
    }

    /**
     * @return list<array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}>
     */
    public function search(string $query): array
    {
        $response = $this->request()
            ->withHeaders(['X-Goog-FieldMask' => collect(explode(',', self::SEARCH_FIELDS))->map(fn (string $f): string => 'places.'.$f)->implode(',')])
            ->post(self::BASE.'/places:searchText', ['textQuery' => $query, 'pageSize' => 8])
            ->throw()
            ->json();

        return array_map(
            fn (array $place): array => $this->normalize($place),
            $response['places'] ?? [],
        );
    }

    /**
     * Text search with pagination: up to 20 places per page, $maxPages pages
     * (Google caps text search at 60 results per query).
     *
     * @return list<array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}>
     */
    public function searchAll(string $query, int $maxPages = 3): array
    {
        $fieldMask = collect(explode(',', self::FIELDS))
            ->map(fn (string $f): string => 'places.'.$f)
            ->push('nextPageToken')
            ->implode(',');

        $results = [];
        $token = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->request()
                ->withHeaders(['X-Goog-FieldMask' => $fieldMask])
                ->post(self::BASE.'/places:searchText', array_filter([
                    'textQuery' => $query,
                    'pageSize' => 20,
                    'pageToken' => $token,
                ]))
                ->throw()
                ->json();

            foreach ($response['places'] ?? [] as $place) {
                $results[] = $this->normalize($place);
            }

            $token = $response['nextPageToken'] ?? null;
            if ($token === null) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}
     */
    public function details(string $placeId): array
    {
        $place = (array) $this->request()
            ->withHeaders(['X-Goog-FieldMask' => self::FIELDS])
            ->get(self::BASE.'/places/'.$placeId)
            ->throw()
            ->json();

        return $this->normalize($place);
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int, rating_distribution: null}
     */
    protected function normalize(array $place): array
    {
        return [
            'place_id' => (string) ($place['id'] ?? ''),
            'name' => (string) ($place['displayName']['text'] ?? ''),
            'address' => $place['formattedAddress'] ?? null,
            'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
            'reviews_count' => (int) ($place['userRatingCount'] ?? 0),
            // The Places API has no star breakdown; only DataForSEO does.
            'rating_distribution' => null,
        ];
    }

    /**
     * A place's latitude/longitude (a separate, cheap field mask), or null.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function coordinates(string $placeId): ?array
    {
        $place = (array) $this->request()
            ->withHeaders(['X-Goog-FieldMask' => 'location'])
            ->get(self::BASE.'/places/'.$placeId)
            ->throw()
            ->json();

        $location = $place['location'] ?? null;
        if (! isset($location['latitude'], $location['longitude'])) {
            return null;
        }

        return ['lat' => (float) $location['latitude'], 'lng' => (float) $location['longitude']];
    }

    /**
     * IANA timezone id at a coordinate via the Google Time Zone API (e.g.
     * "Asia/Dubai"), or null. Requires the Time Zone API enabled on the key.
     */
    public function timezoneAt(float $lat, float $lng): ?string
    {
        $response = (array) Http::acceptJson()
            ->timeout(15)
            ->connectTimeout(5)
            ->get('https://maps.googleapis.com/maps/api/timezone/json', [
                'location' => $lat.','.$lng,
                'timestamp' => now()->timestamp,
                'key' => (string) config('services.google.places_key'),
            ])
            ->throw()
            ->json();

        return ($response['status'] ?? '') === 'OK' && filled($response['timeZoneId'] ?? null)
            ? (string) $response['timeZoneId']
            : null;
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders(['X-Goog-Api-Key' => (string) config('services.google.places_key')])
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }
}
