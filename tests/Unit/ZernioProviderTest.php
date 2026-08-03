<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Reviews\ZernioProvider;
use Carbon\Carbon;
use DateTime;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;
use Zernio\Api\GMBReviewsApi;
use Zernio\ApiException;
use Zernio\Model\GetGoogleBusinessReviews200Response;
use Zernio\Model\GetGoogleBusinessReviews200ResponseReviewsInner;

/**
 * The retry helper and the incremental review listing are what keep the queued
 * per-location sync inside its timeout: retries must give up before eating the
 * job's clock, and pagination must stop once a page falls before $since. The
 * provider is built without its constructor (no live SDK config needed) and the
 * reviews API is swapped in via reflection.
 */
class ZernioProviderTest extends TestCase
{
    private function provider(): ZernioProvider
    {
        return (new ReflectionClass(ZernioProvider::class))->newInstanceWithoutConstructor();
    }

    private function providerWithReviewsApi(GMBReviewsApi $api): ZernioProvider
    {
        $provider = $this->provider();
        (new ReflectionProperty(ZernioProvider::class, 'reviews'))->setValue($provider, $api);

        return $provider;
    }

    private function invokeRetry(ZernioProvider $provider, callable $request, ?float $budgetSeconds = null): mixed
    {
        return (new ReflectionMethod($provider, 'withRateLimitRetry'))->invoke($provider, $request, $budgetSeconds);
    }

    private function zernioReview(string $id, string $createdAt, ?string $updatedAt = null): GetGoogleBusinessReviews200ResponseReviewsInner
    {
        $review = new GetGoogleBusinessReviews200ResponseReviewsInner;
        $review->setId($id);
        $review->setRating(5);
        $review->setCreateTime(new DateTime($createdAt));

        if ($updatedAt !== null) {
            $review->setUpdateTime(new DateTime($updatedAt));
        }

        return $review;
    }

    /**
     * @param  GetGoogleBusinessReviews200ResponseReviewsInner[]  $reviews
     */
    private function page(array $reviews, ?string $nextPageToken = null): GetGoogleBusinessReviews200Response
    {
        $response = new GetGoogleBusinessReviews200Response;
        $response->setLocationId('loc-1');
        $response->setReviews($reviews);
        $response->setNextPageToken($nextPageToken);

        return $response;
    }

    public function test_retry_rethrows_instead_of_sleeping_past_the_budget(): void
    {
        $calls = 0;
        $start = microtime(true);

        try {
            $this->invokeRetry($this->provider(), function () use (&$calls): void {
                $calls++;

                throw new ApiException('upstream timed out', 500);
            }, budgetSeconds: 0.0);

            $this->fail('ApiException expected');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getCode());
        }

        $this->assertSame(1, $calls);
        $this->assertLessThan(1.0, microtime(true) - $start);
    }

    public function test_hard_4xx_errors_fail_fast_without_retrying(): void
    {
        $calls = 0;

        try {
            $this->invokeRetry($this->provider(), function () use (&$calls): void {
                $calls++;

                throw new ApiException('bad request', 400);
            });

            $this->fail('ApiException expected');
        } catch (ApiException $e) {
            $this->assertSame(400, $e->getCode());
        }

        $this->assertSame(1, $calls);
    }

    public function test_rate_limit_is_retried_within_the_budget(): void
    {
        $calls = 0;

        $result = $this->invokeRetry($this->provider(), function () use (&$calls): string {
            if ($calls++ === 0) {
                throw new ApiException('rate limited', 429, ['X-RateLimit-Reset' => [(string) (time() + 1)]]);
            }

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(2, $calls);
    }

    public function test_connection_timeout_is_retried_within_the_budget(): void
    {
        $calls = 0;

        // Guzzle ConnectException reaches us as ApiException code 0.
        $result = $this->invokeRetry($this->provider(), function () use (&$calls): string {
            if ($calls++ === 0) {
                throw new ApiException('cURL error 28: Operation timed out after 30002 milliseconds', 0);
            }

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(2, $calls);
    }

    public function test_list_reviews_stops_paginating_once_a_page_is_older_than_since(): void
    {
        $api = Mockery::mock(GMBReviewsApi::class);
        $api->shouldReceive('getGoogleBusinessReviews')
            ->twice()
            ->andReturn(
                $this->page([$this->zernioReview('r-fresh', '2026-07-27 10:00:00')], 'page-2'),
                // Whole page older than $since; page-3 must never be fetched.
                $this->page([$this->zernioReview('r-old', '2026-05-01 10:00:00')], 'page-3'),
            );

        $reviews = $this->providerWithReviewsApi($api)
            ->listReviews('acc-1', 'loc-1', Carbon::parse('2026-07-20 00:00:00'));

        $this->assertCount(1, $reviews);
        $this->assertSame('r-fresh', $reviews[0]->externalId);
    }

    public function test_list_reviews_cuts_off_by_update_time_not_create_time(): void
    {
        $api = Mockery::mock(GMBReviewsApi::class);
        $api->shouldReceive('getGoogleBusinessReviews')
            ->once()
            ->andReturn($this->page([
                // Old review whose reply/edit just bumped its updateTime.
                $this->zernioReview('r-edited', '2025-01-01 10:00:00', '2026-07-27 09:00:00'),
                $this->zernioReview('r-stale', '2025-01-01 10:00:00', '2025-01-02 10:00:00'),
            ]));

        $reviews = $this->providerWithReviewsApi($api)
            ->listReviews('acc-1', 'loc-1', Carbon::parse('2026-07-20 00:00:00'));

        $this->assertCount(1, $reviews);
        $this->assertSame('r-edited', $reviews[0]->externalId);
    }

    public function test_list_reviews_without_since_walks_every_page(): void
    {
        $api = Mockery::mock(GMBReviewsApi::class);
        $api->shouldReceive('getGoogleBusinessReviews')
            ->twice()
            ->andReturn(
                $this->page([$this->zernioReview('r-1', '2026-07-27 10:00:00')], 'page-2'),
                $this->page([$this->zernioReview('r-2', '2020-01-01 10:00:00')]),
            );

        $reviews = $this->providerWithReviewsApi($api)->listReviews('acc-1', 'loc-1');

        $this->assertCount(2, $reviews);
    }
}
