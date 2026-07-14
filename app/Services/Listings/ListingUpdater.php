<?php

declare(strict_types=1);

namespace App\Services\Listings;

use App\Models\Location;
use App\Services\Zernio\ZernioRestClient;
use Carbon\CarbonImmutable;

/**
 * Maps the listing edit form to Zernio's gmb-location-details PATCH (a proxy
 * of Google's locations.patch with an updateMask) and pushes it. A copy of the
 * pushed values is kept on locations.listing_data as an offline fallback for
 * prefilling the form.
 */
class ListingUpdater
{
    /**
     * Google's fixed set of social-profile URL attributes (category-independent).
     *
     * @var array<string, string> attribute suffix => label
     */
    public const SOCIAL_ATTRIBUTES = [
        'url_facebook' => 'Facebook',
        'url_instagram' => 'Instagram',
        'url_youtube' => 'YouTube',
        'url_twitter' => 'X (Twitter)',
        'url_linkedin' => 'LinkedIn',
        'url_tiktok' => 'TikTok',
        'url_pinterest' => 'Pinterest',
    ];

    public function __construct(protected ZernioRestClient $client) {}

    /**
     * @param  array<string, mixed>  $data  form state: description, phone, website, opening_hours[], special_hours[], socials{}
     */
    public function push(Location $location, array $data): void
    {
        $payload = $this->buildPayload($data);

        // Google's locations.patch rejects requests without an updateMask —
        // nothing to send means nothing to push.
        if (array_key_exists('updateMask', $payload)) {
            $this->client->updateLocationDetails(
                (string) $location->zernio_account_id,
                (string) $location->external_id,
                $payload,
            );
        }

        $socials = $this->buildSocialAttributes((array) ($data['socials'] ?? []));

        if ($socials !== []) {
            $this->client->updateAttributes(
                (string) $location->zernio_account_id,
                (string) $location->external_id,
                $socials,
            );
        }

        $location->forceFill([
            'listing_data' => [
                'description' => $data['description'] ?? null,
                'additional_phones' => array_values(array_filter((array) ($data['additional_phones'] ?? []))),
                'opening_hours' => array_values($data['opening_hours'] ?? []),
                'special_hours' => array_values($data['special_hours'] ?? []),
            ],
            'phone' => $data['phone'] ?? $location->phone,
            'website_url' => $data['website'] ?? $location->website_url,
        ])->save();
    }

    /**
     * Bulk-edit path: push ONLY hours (regular and/or special), leaving the
     * rest of the profile and the stored listing_data copy untouched. A null
     * section means "don't change it".
     *
     * @param  ?array<int, array<string, mixed>>  $openingHours
     * @param  ?array<int, array<string, mixed>>  $specialHours
     */
    public function pushHours(Location $location, ?array $openingHours, ?array $specialHours): void
    {
        $payload = $this->buildPayload([
            'opening_hours' => $openingHours ?? [],
            'special_hours' => $specialHours ?? [],
        ]);

        if (! array_key_exists('updateMask', $payload)) {
            return;
        }

        $this->client->updateLocationDetails(
            (string) $location->zernio_account_id,
            (string) $location->external_id,
            $payload,
        );

        $stored = (array) ($location->listing_data ?? []);
        if ($openingHours !== null) {
            $stored['opening_hours'] = array_values($openingHours);
        }
        if ($specialHours !== null) {
            $stored['special_hours'] = array_values($specialHours);
        }

        $location->forceFill(['listing_data' => $stored])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildPayload(array $data): array
    {
        $payload = [];
        $mask = [];

        if (filled($data['description'] ?? null)) {
            $payload['profile'] = ['description' => $data['description']];
            $mask[] = 'profile.description';
        }
        $additionalPhones = array_values(array_filter((array) ($data['additional_phones'] ?? [])));

        if (filled($data['phone'] ?? null) || $additionalPhones !== []) {
            $payload['phoneNumbers'] = array_filter([
                'primaryPhone' => $data['phone'] ?? null,
                'additionalPhones' => $additionalPhones ?: null,
            ]);
            $mask[] = 'phoneNumbers';
        }
        if (filled($data['website'] ?? null)) {
            $payload['websiteUri'] = $data['website'];
            $mask[] = 'websiteUri';
        }

        $periods = [];
        foreach ($data['opening_hours'] ?? [] as $row) {
            if (blank($row['day'] ?? null) || blank($row['open'] ?? null) || blank($row['close'] ?? null)) {
                continue;
            }

            $periods[] = [
                'openDay' => $row['day'],
                'openTime' => $this->time((string) $row['open']),
                'closeDay' => $row['day'],
                'closeTime' => $this->time((string) $row['close']),
            ];
        }
        if ($periods !== []) {
            $payload['regularHours'] = ['periods' => $periods];
            $mask[] = 'regularHours';
        }

        $specialPeriods = [];
        foreach ($data['special_hours'] ?? [] as $row) {
            if (blank($row['start_date'] ?? null) || blank($row['end_date'] ?? null)) {
                continue;
            }

            $closed = (bool) ($row['closed'] ?? false);
            $entry = [
                'startDate' => $this->date((string) $row['start_date']),
                'endDate' => $this->date((string) $row['end_date']),
                'closed' => $closed,
            ];

            if (! $closed) {
                if (blank($row['open'] ?? null) || blank($row['close'] ?? null)) {
                    continue; // open/close times are required when not closed
                }
                $entry['openTime'] = $this->time((string) $row['open']);
                $entry['closeTime'] = $this->time((string) $row['close']);
            }

            $specialPeriods[] = $entry;
        }
        if ($specialPeriods !== []) {
            $payload['specialHours'] = ['specialHourPeriods' => $specialPeriods];
            $mask[] = 'specialHours';
        }

        if ($mask !== []) {
            $payload['updateMask'] = implode(',', $mask);
        }

        return $payload;
    }

    /**
     * Filled social URLs → Zernio gmb-attributes PUT entries. Empty fields are
     * skipped (the attribute on Google stays as it is).
     *
     * @param  array<string, ?string>  $socials  keyed by SOCIAL_ATTRIBUTES suffix
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    public function buildSocialAttributes(array $socials): array
    {
        $attributes = [];

        foreach (array_keys(self::SOCIAL_ATTRIBUTES) as $key) {
            $url = trim((string) ($socials[$key] ?? ''));

            if ($url === '') {
                continue;
            }

            if (! preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://'.$url;
            }

            $attributes[] = [
                'name' => 'attributes/'.$key,
                'values' => [$url],
            ];
        }

        return $attributes;
    }

    /** "09:30:00" → "09:30" (the API uses HH:MM strings). */
    protected function time(string $value): string
    {
        [$hours, $minutes] = array_pad(explode(':', $value), 2, '00');

        return sprintf('%02d:%02d', (int) $hours, (int) $minutes);
    }

    /**
     * "2026-12-24" → Google's { year, month, day }.
     *
     * @return array{year: int, month: int, day: int}
     */
    protected function date(string $value): array
    {
        $day = CarbonImmutable::parse($value);

        return ['year' => $day->year, 'month' => $day->month, 'day' => $day->day];
    }
}
