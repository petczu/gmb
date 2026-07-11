<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Services\Reviews\Data\LocationData;
use App\Services\Reviews\Data\ReviewData;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Zernio\Api\ConnectApi;
use Zernio\Api\GMBReviewsApi;
use Zernio\ApiException;
use Zernio\Configuration;
use Zernio\Model\GetGoogleBusinessReviews200ResponseReviewsInner as ZReview;
use Zernio\Model\ReplyToGoogleBusinessReviewRequest;
use Zernio\Model\UpdateGmbLocationRequest;

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

    /**
     * Run a Zernio request, waiting out 429 rate limits. Zernio's limits are
     * per API key with a per-second window on analytics endpoints; on 429 we
     * wait until X-RateLimit-Reset (capped, fallback: exponential backoff) and
     * retry a few times before giving up. The sync runs in a queued job, so
     * blocking here simply spreads the calls out.
     *
     * @template T
     *
     * @param  callable(): T  $request
     * @return T
     */
    private function withRateLimitRetry(callable $request): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                return $request();
            } catch (ApiException $e) {
                if ($e->getCode() !== 429 || $attempts >= 4) {
                    throw $e;
                }

                $attempts++;
                $headers = array_change_key_case($e->getResponseHeaders() ?? [], CASE_LOWER);
                $reset = (int) (($headers['x-ratelimit-reset'][0] ?? $headers['x-ratelimit-reset'] ?? 0));
                $wait = $reset > 0 ? max(1, min(30, $reset - time())) : min(30, 2 ** $attempts);

                sleep($wait);
            }
        }
    }

    public function listLocations(string $accountId): array
    {
        $response = $this->withRateLimitRetry(fn () => $this->connect->getGmbLocations($accountId));

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
            $response = $this->withRateLimitRetry(
                fn () => $this->reviews->getGoogleBusinessReviews($accountId, $locationExternalId, 50, $pageToken),
            );
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

    public function reply(string $accountId, string $reviewExternalId, string $comment, ?string $locationExternalId = null): void
    {
        $this->withSelectedLocation($accountId, $locationExternalId, function () use ($accountId, $reviewExternalId, $comment): void {
            $request = new ReplyToGoogleBusinessReviewRequest;
            $request->setComment($comment);

            $this->reviews->replyToGoogleBusinessReview($accountId, $reviewExternalId, $request);
        });
    }

    public function deleteReply(string $accountId, string $reviewExternalId, ?string $locationExternalId = null): void
    {
        $this->withSelectedLocation($accountId, $locationExternalId, function () use ($accountId, $reviewExternalId): void {
            $this->reviews->deleteGoogleBusinessReviewReply($accountId, $reviewExternalId);
        });
    }

    /**
     * Zernio's write endpoints act on the account's *selected* location, and
     * one account can serve several tracked locations. Switch the selection to
     * the target location, then run the write; without it, writes for any
     * non-selected location fail with 404 "GBP resource not found".
     *
     * The lock serializes concurrent writers (queue workers) on the same
     * account so a parallel switch can't land between our switch and write. A
     * mismatch can't publish to the wrong business, review ids are per
     * location, so the worst case stays a 404.
     */
    private function withSelectedLocation(string $accountId, ?string $locationExternalId, callable $write): void
    {
        if (blank($locationExternalId)) {
            $write();

            return;
        }

        $lock = Cache::lock("zernio:write:{$accountId}", seconds: 60);

        $lock->block(30, function () use ($accountId, $locationExternalId, $write): void {
            $this->withRateLimitRetry(fn () => $this->connect->updateGmbLocation(
                $accountId,
                (new UpdateGmbLocationRequest)->setSelectedLocationId($locationExternalId),
            ));

            $write();
        });
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
