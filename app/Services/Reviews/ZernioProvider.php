<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Services\Reviews\Data\LocationData;
use App\Services\Reviews\Data\ReviewData;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use GuzzleHttp\Client as GuzzleClient;
use Zernio\Api\ConnectApi;
use Zernio\Api\GMBReviewsApi;
use Zernio\Configuration;
use Zernio\Model\GetGoogleBusinessReviews200ResponseReviewsInner as ZReview;
use Zernio\Model\ReplyToGoogleBusinessReviewRequest;

/**
 * Live Zernio implementation. Wraps zernio-dev/zernio-php and normalizes its
 * generated models into our provider-agnostic DTOs. Constructed per-workspace
 * with that workspace's access token (see ReviewProviderFactory).
 */
class ZernioProvider implements ReviewProvider
{
    private GMBReviewsApi $reviews;

    private ConnectApi $connect;

    public function __construct(?string $accessToken)
    {
        $config = Configuration::getDefaultConfiguration()->setAccessToken((string) $accessToken);

        // ZERNIO_BASE_URL ends with /v1; SDK operation paths already include
        // /v1, so strip a trailing /v1 before setting the host.
        if ($base = config('services.reviews.zernio_base_url')) {
            $config->setHost(rtrim((string) preg_replace('#/v1/?$#', '', (string) $base), '/'));
        }

        $http = new GuzzleClient(['timeout' => 30, 'connect_timeout' => 5]);

        $this->reviews = new GMBReviewsApi($http, $config);
        $this->connect = new ConnectApi($http, $config);
    }

    public function listLocations(string $accountId): array
    {
        $response = $this->connect->getGmbLocations($accountId);

        return array_map(function ($loc): LocationData {
            return new LocationData(
                externalId: (string) $loc->getId(),
                name: (string) ($loc->getName() ?? 'Untitled location'),
                address: $loc->getAddress(),
                websiteUrl: $loc->getWebsiteUrl(),
            );
        }, $response->getLocations() ?? []);
    }

    public function listReviews(string $accountId, ?string $locationExternalId = null, ?CarbonInterface $since = null): array
    {
        $out = [];
        $pageToken = null;

        do {
            $response = $this->reviews->getGoogleBusinessReviews($accountId, $locationExternalId, 50, $pageToken);
            $locId = (string) ($response->getLocationId() ?? $locationExternalId ?? '');

            foreach ($response->getReviews() ?? [] as $r) {
                $createdAt = $r->getCreateTime() ? Carbon::instance($r->getCreateTime()) : null;

                if ($since !== null && $createdAt !== null && $createdAt->lessThan($since)) {
                    continue;
                }

                $reply = $r->getReviewReply();

                $out[] = new ReviewData(
                    externalId: (string) ($r->getId() ?? $r->getName()),
                    locationExternalId: $locId,
                    rating: $this->rating($r),
                    authorName: $r->getReviewer()?->getDisplayName(),
                    text: $r->getComment(),
                    reviewLink: null,
                    createdAtExternal: $createdAt,
                    replyText: $reply?->getComment(),
                    repliedAt: $reply?->getUpdateTime() ? Carbon::instance($reply->getUpdateTime()) : null,
                );
            }

            $pageToken = $response->getNextPageToken();
        } while (! empty($pageToken));

        return $out;
    }

    public function reply(string $accountId, string $reviewExternalId, string $comment): void
    {
        $request = new ReplyToGoogleBusinessReviewRequest();
        $request->setComment($comment);

        $this->reviews->replyToGoogleBusinessReview($accountId, $reviewExternalId, $request);
    }

    public function deleteReply(string $accountId, string $reviewExternalId): void
    {
        $this->reviews->deleteGoogleBusinessReviewReply($accountId, $reviewExternalId);
    }

    private function rating(ZReview $r): int
    {
        $n = $r->getRating();
        if (is_int($n) && $n >= 1 && $n <= 5) {
            return $n;
        }

        return match (strtoupper((string) $r->getStarRating())) {
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
            default => 0,
        };
    }
}
