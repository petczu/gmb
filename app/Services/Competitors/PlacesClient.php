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
            ->withHeaders(['X-Goog-FieldMask' => collect(explode(',', self::FIELDS))->map(fn (string $f): string => 'places.'.$f)->implode(',')])
            ->post(self::BASE.'/places:searchText', ['textQuery' => $query, 'pageSize' => 8])
            ->throw()
            ->json();

        return array_map(
            fn (array $place): array => $this->normalize($place),
            $response['places'] ?? [],
        );
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
     * @return array{place_id: string, name: string, address: ?string, rating: ?float, reviews_count: int}
     */
    protected function normalize(array $place): array
    {
        return [
            'place_id' => (string) ($place['id'] ?? ''),
            'name' => (string) ($place['displayName']['text'] ?? ''),
            'address' => $place['formattedAddress'] ?? null,
            'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
            'reviews_count' => (int) ($place['userRatingCount'] ?? 0),
        ];
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders(['X-Goog-Api-Key' => (string) config('services.google.places_key')])
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(5);
    }
}
