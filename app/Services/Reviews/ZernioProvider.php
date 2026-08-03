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
     * Hard wall-clock ceiling (seconds) for ONE request including all its
     * in-process retry waits. Without it, a 429/5xx storm makes the retries
     * silently eat the queued job's $timeout: the worker kills the job with no
     * exception, the attempt is burned unreported, and after $tries such kills
     * the job dies as MaxAttemptsExceededException. Rethrowing instead hands
     * the wait to the job's own $backoff, which is visible and far longer.
     */
    private const RETRY_BUDGET_SECONDS = 90;

    /**
     * Run a Zernio request, waiting out transient failures. Two cases:
     * - 429 rate limits (per API key, per-second window on analytics
     *   endpoints): wait until X-RateLimit-Reset (capped, fallback:
     *   exponential backoff).
     * - 5xx: Zernio proxies Google, and its upstream calls regularly time out
     *   ("connection timed out" as a 500); a short backoff and retry usually
     *   succeeds, and beats burning one of the queued job's own attempts.
     * Retries are bounded in count AND wall-clock time ($budgetSeconds): once
     * the next wait would pass the budget, the error is rethrown so the queued
     * job fails visibly and retries via its own backoff.
     *
     * @template T
     *
     * @param  callable(): T  $request
     * @return T
     */
    private function withRateLimitRetry(callable $request, ?float $budgetSeconds = null): mixed
    {
        $attempts = 0;
        $deadline = microtime(true) + ($budgetSeconds ?? self::RETRY_BUDGET_SECONDS);

        while (true) {
            try {
                return $request();
            } catch (ApiException $e) {
                $code = $e->getCode();
                // Connection timeouts / transport errors surface as code 0
                // (Guzzle ConnectException) — retry those too, not just HTTP
                // 429/5xx. Zernio times out under load and a couple of retries
                // usually clear it (Sentry REPUNIO-G).
                $retryable = $code === 429 || ($code >= 500 && $code < 600) || self::isTransportError($e);

                if (! $retryable || $attempts >= 4) {
                    throw $e;
                }

                $attempts++;

                if ($code === 429) {
                    $headers = array_change_key_case($e->getResponseHeaders() ?? [], CASE_LOWER);
                    $reset = (int) (($headers['x-ratelimit-reset'][0] ?? $headers['x-ratelimit-reset'] ?? 0));
                    $wait = $reset > 0 ? max(1, min(30, $reset - time())) : min(30, 2 ** $attempts);
                } else {
                    $wait = min(30, 3 * $attempts); // 3s, 6s, 9s, 12s
                }

                if (microtime(true) + $wait > $deadline) {
                    throw $e;
                }

                sleep($wait);
            }
        }
    }

    /**
     * Whether an ApiException is a network transport failure (timeout, DNS,
     * connection drop) rather than an HTTP response. These carry code 0 from
     * the SDK, so they are matched by message.
     */
    private static function isTransportError(ApiException $e): bool
    {
        $message = strtolower($e->getMessage());

        foreach (['timed out', 'timeout', 'curl error 28', 'could not resolve host', 'connection refused', 'connection reset', 'failed to connect'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
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
            $pageHasFresh = false;

            foreach ($response->getReviews() ?? [] as $r) {
                $createdAt = $r->getCreateTime() ? Carbon::instance($r->getCreateTime()) : null;

                // Cut off by UPDATE time, not create time: an old review whose
                // reply or text just changed carries a fresh updateTime and
                // must be re-synced.
                $updatedAt = $r->getUpdateTime() ? Carbon::instance($r->getUpdateTime()) : $createdAt;

                if ($since !== null && $updatedAt !== null && $updatedAt->lessThan($since)) {
                    continue;
                }
                $pageHasFresh = true;

                $reply = $r->getReviewReply();
                $photos = array_values(array_filter(array_map(
                    fn ($p): ?string => $p->getUrl(),
                    $r->getPhotos() ?? [],
                )));

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
                    photoCount: (int) ($r->getPhotoCount() ?? count($photos)),
                    photos: $photos,
                );
            }

            $pageToken = $response->getNextPageToken();

            // Google (which Zernio proxies) returns reviews newest-updated
            // first, so once a whole page falls before $since every later page
            // does too — stop paginating instead of walking the full history.
            if ($since !== null && ! $pageHasFresh) {
                break;
            }
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

            // Zernio applies the selection switch asynchronously, so a write
            // fired immediately can still hit the PREVIOUS selection and publish
            // to the wrong location. Confirm the account's selected location is
            // actually the target before writing; abort (job retries) if it
            // never converges, rather than post the reply to the wrong business.
            $confirmed = false;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $current = $this->withRateLimitRetry(
                    fn () => $this->connect->getGmbLocations($accountId)
                )->getSelectedLocationId();

                if ((string) $current === (string) $locationExternalId) {
                    $confirmed = true;
                    break;
                }

                usleep(400_000); // 0.4s between checks
            }

            if (! $confirmed) {
                throw new \RuntimeException(
                    "Zernio selected location did not switch to {$locationExternalId} on account {$accountId}; "
                    .'aborting write to avoid posting to the wrong location.'
                );
            }

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
