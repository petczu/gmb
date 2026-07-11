<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Services\Reviews\Data\LocationData;
use App\Services\Reviews\Data\ReviewData;
use Carbon\CarbonInterface;

/**
 * The single seam between the app and whatever review backend we use.
 * v1 implementations: FakeReviewProvider (dev/seed) and ZernioProvider (live).
 */
interface ReviewProvider
{
    /**
     * Locations connected under a Zernio account.
     *
     * @return LocationData[]
     */
    public function listLocations(string $accountId): array;

    /**
     * Reviews for an account, optionally filtered to one location and to those
     * created/updated since a given time.
     *
     * @return ReviewData[]
     */
    public function listReviews(string $accountId, ?string $locationExternalId = null, ?CarbonInterface $since = null): array;

    /**
     * Post (or update) the reply to a review.
     *
     * $locationExternalId is the Google location the review belongs to: Zernio
     * writes act on the account's *selected* location, and one account can
     * serve several tracked locations, so the provider switches the selection
     * to this location first (PUT /accounts/{id}/gmb-locations).
     */
    public function reply(string $accountId, string $reviewExternalId, string $comment, ?string $locationExternalId = null): void;

    /**
     * Delete the reply on a review. $locationExternalId as in reply().
     */
    public function deleteReply(string $accountId, string $reviewExternalId, ?string $locationExternalId = null): void;
}
