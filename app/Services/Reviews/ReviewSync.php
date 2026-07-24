<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Mail\LocationSyncedMail;
use App\Mail\NewReviewsMail;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Notifications\ChatChannels;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Webhooks\WebhookDispatcher;
use App\Support\SyncFailure;
use App\Webhooks\WebhookEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Refreshes reviews for the locations a workspace is TRACKING. Locations are
 * created explicitly when the user connects them (the picker), not discovered
 * here — so disconnecting a location keeps it gone. Reviews are fetched per
 * location (Zernio returns reviews for one location per call).
 *
 * Two entry points:
 *  - {@see self::syncWorkspace()} runs every location synchronously and sends a
 *    single aggregated digest. Used by the console and manual "sync now".
 *  - {@see self::syncLocation()} does ONE location; the queued fan-out
 *    (SyncLocationReviewsJob) uses it so many locations are spread over time and
 *    a slow one never blocks the rest or trips Zernio's rate limit.
 *
 * Both initialize the tenant themselves and restore the previous tenant.
 */
class ReviewSync
{
    public function __construct(private readonly ReviewProviderFactory $factory) {}

    /**
     * @return array{locations:int, reviews:int, errors:int}
     */
    public function syncWorkspace(Workspace $workspace): array
    {
        $stats = ['locations' => 0, 'reviews' => 0, 'errors' => 0];

        $previous = tenant();
        tenancy()->initialize($workspace);

        // Accumulated across all locations so we send ONE digest per run.
        $newCount = 0;
        $samples = [];
        $newLocations = [];
        $newLocationIds = [];
        $newReviewIds = [];
        $firstSynced = [];

        try {
            $locations = Location::query()->get();
            $stats['locations'] = $locations->count();

            foreach ($locations as $location) {
                $result = $this->syncLocation($workspace, $location, tenancyManaged: false);

                $stats['reviews'] += $result['reviews'];
                $stats['errors'] += $result['error'] ? 1 : 0;
                $newCount += $result['new_count'];

                foreach ($result['samples'] as $sample) {
                    if (count($samples) < 5) {
                        $samples[] = $sample;
                    }
                }
                foreach ($result['new_review_ids'] as $id) {
                    $newReviewIds[] = $id;
                }
                if ($result['new_count'] > 0) {
                    $newLocations[$location->name] = true;
                    $newLocationIds[(int) $location->id] = true;
                }
                if ($result['first_synced'] !== null) {
                    $firstSynced[] = $result['first_synced'];
                }
            }
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }

        // Notifications run AFTER tenant context is restored.
        $this->sendFirstSyncedMail($workspace, $firstSynced);

        if ($newCount > 0) {
            $label = $newLocations !== [] ? implode(', ', array_keys($newLocations)) : (string) $workspace->name;
            $digestLocationId = count($newLocationIds) === 1 ? (int) array_key_first($newLocationIds) : null;
            $this->sendReviewsDigest($workspace, $label, $newCount, $samples, $newReviewIds, $digestLocationId);
        }

        return $stats;
    }

    /**
     * Sync a SINGLE location. When $tenancyManaged is true it initializes and
     * restores tenancy itself (the queued per-location job path); when false the
     * caller has already entered the tenant (the syncWorkspace loop).
     *
     * @return array{reviews:int, error:bool, new_count:int, samples:array<int, array<string, mixed>>, new_review_ids:array<int, int>, first_synced:?array{name:string, count:int, rating:?float}}
     */
    public function syncLocation(Workspace $workspace, Location $location, bool $tenancyManaged = true): array
    {
        $previous = null;
        if ($tenancyManaged) {
            $previous = tenant();
            tenancy()->initialize($workspace);
        }

        $provider = $this->factory->make();
        $result = [
            'reviews' => 0,
            'error' => false,
            'new_count' => 0,
            'samples' => [],
            'new_review_ids' => [],
            'first_synced' => null,
        ];

        try {
            $accountId = $location->zernio_account_id ?: 'fake-account';
            $firstSyncForLocation = $location->last_synced_at === null;

            try {
                $reviews = $provider->listReviews($accountId, $location->external_id);
            } catch (Throwable $e) {
                Log::warning('ReviewSync: location reviews failed', [
                    'location' => $location->external_id,
                    'error' => $e->getMessage(),
                ]);
                if (! SyncFailure::isTransient($e)) {
                    report($e);
                }
                $location->forceFill(['last_sync_error' => Str::limit($e->getMessage(), 500)])->save();
                $result['error'] = true;

                return $result;
            }

            $externalIds = array_map(fn ($rd) => $rd->externalId, $reviews);
            $existingMap = $externalIds
                ? Review::query()->whereIn('external_review_id', $externalIds)->get()->keyBy('external_review_id')
                : collect();

            foreach ($reviews as $rd) {
                $existing = $existingMap->get($rd->externalId);

                $attributes = [
                    'location_id' => $location->id,
                    'author_name' => $rd->authorName,
                    'rating' => $rd->rating,
                    'text' => $rd->text,
                    'photo_count' => $rd->photoCount,
                    'photos' => $rd->photos,
                    'review_link' => $rd->reviewLink,
                    'created_at_external' => $rd->createdAtExternal,
                    'synced_at' => now(),
                ];

                if ($rd->replyText !== null) {
                    $attributes['reply_text'] = $rd->replyText;
                    $attributes['replied_at'] = $rd->repliedAt;
                    $attributes['reply_status'] = 'published';
                    $attributes['reply_source'] = $existing?->reply_source ?? 'external';
                } elseif ($existing === null || $existing->reply_text === null) {
                    $attributes['reply_text'] = null;
                    $attributes['replied_at'] = null;
                    $attributes['reply_status'] = null;
                    $attributes['reply_source'] = null;
                }

                $review = Review::query()->updateOrCreate(
                    ['external_review_id' => $rd->externalId],
                    $attributes,
                );
                $result['reviews']++;

                if ($review->wasRecentlyCreated && ! $firstSyncForLocation) {
                    $result['new_count']++;
                    $result['new_review_ids'][] = (int) $review->id;

                    $review->setRelation('location', $location);
                    app(WebhookDispatcher::class)
                        ->dispatch(WebhookEvents::REVIEW_CREATED, $review->toWebhookPayload());

                    if (count($result['samples']) < 5) {
                        $result['samples'][] = [
                            'author' => (string) ($review->author_name ?: '—'),
                            'rating' => (int) $review->rating,
                            'snippet' => Str::limit((string) ($review->originalText() ?? $review->text), 120),
                            'location' => (string) $location->name,
                        ];
                    }
                }
            }

            $agg = $location->reviews()
                ->selectRaw('count(*) as total, avg(rating) as avg_rating')
                ->first();

            $location->forceFill([
                'reviews_count' => (int) $agg->total,
                'rating' => $agg->avg_rating !== null ? round((float) $agg->avg_rating, 1) : null,
                'last_synced_at' => now(),
                'last_sync_error' => null,
            ])->save();

            if ($firstSyncForLocation) {
                $result['first_synced'] = [
                    'name' => (string) $location->name,
                    'count' => (int) $agg->total,
                    'rating' => $agg->avg_rating !== null ? round((float) $agg->avg_rating, 1) : null,
                ];
            }
        } finally {
            if ($tenancyManaged) {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return $result;
    }

    /**
     * Send the per-location notifications for a result returned by
     * {@see self::syncLocation()}. Runs from the queued per-location job AFTER
     * the tenant context is restored.
     *
     * @param  array{reviews:int, error:bool, new_count:int, samples:array<int, array<string, mixed>>, new_review_ids:array<int, int>, first_synced:?array{name:string, count:int, rating:?float}}  $result
     */
    public function notifyLocationResult(Workspace $workspace, Location $location, array $result): void
    {
        if ($result['first_synced'] !== null) {
            $this->sendFirstSyncedMail($workspace, [$result['first_synced']]);
        }

        if ($result['new_count'] > 0) {
            $this->sendReviewsDigest(
                $workspace,
                (string) $location->name,
                $result['new_count'],
                $result['samples'],
                $result['new_review_ids'],
                (int) $location->id,
            );
        }
    }

    /**
     * "Your reviews are in" — one email covering the locations whose FIRST
     * import just finished.
     *
     * @param  list<array{name:string, count:int, rating:?float}>  $firstSynced
     */
    private function sendFirstSyncedMail(Workspace $workspace, array $firstSynced): void
    {
        if ($firstSynced === []) {
            return;
        }

        try {
            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new LocationSyncedMail(
                    name: $name,
                    locations: $firstSynced,
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Location synced email failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * "N new reviews" digest email + Slack/Telegram. Best-effort.
     *
     * @param  array<int, array<string, mixed>>  $samples
     * @param  array<int, int>  $newReviewIds
     */
    private function sendReviewsDigest(Workspace $workspace, string $locationLabel, int $newCount, array $samples, array $newReviewIds, ?int $locationId): void
    {
        // Deep-link so the button lands on exactly the new reviews.
        $base = rtrim((string) config('app.url'), '/').'/reviews';
        $reviewsUrl = match (true) {
            $newCount === 1 && $newReviewIds !== [] => $base.'?review='.$newReviewIds[0],
            $newReviewIds !== [] => $base.'?reviews='.implode(',', array_slice($newReviewIds, 0, 50)),
            default => $base,
        };

        try {
            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::REPUTATION,
                fn (string $name, string $lang) => new NewReviewsMail(
                    name: $name,
                    count: $newCount,
                    locationName: $locationLabel,
                    samples: $samples,
                    reviewsUrl: $reviewsUrl,
                    lang: $lang,
                ),
                locationId: $locationId,
            );
        } catch (Throwable $e) {
            Log::warning('New reviews email failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);
        }

        ChatChannels::send(
            $workspace,
            NotificationCategory::REPUTATION,
            'new_reviews',
            ['count' => $newCount, 'location' => $locationLabel, 'url' => $reviewsUrl],
        );
    }
}
