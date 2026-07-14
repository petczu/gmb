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
use App\Webhooks\WebhookEvents;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Refreshes reviews for the locations a workspace is TRACKING. Locations are
 * created explicitly when the user connects them (the picker), not discovered
 * here — so disconnecting a location keeps it gone. Reviews are fetched per
 * location (Zernio returns reviews for one location per call).
 *
 * Intended to be called from a CENTRAL context (scheduler / console); it
 * initializes the tenant itself and restores the previous tenant afterwards.
 */
class ReviewSync
{
    public function __construct(private readonly ReviewProviderFactory $factory) {}

    /**
     * @return array{locations:int, reviews:int, errors:int}
     */
    public function syncWorkspace(Workspace $workspace): array
    {
        $provider = $this->factory->make();
        $stats = ['locations' => 0, 'reviews' => 0, 'errors' => 0];

        $previous = tenant();
        tenancy()->initialize($workspace);

        // Collected across all locations so we send ONE digest email per sync.
        $newCount = 0;
        /** @var array<int, array{author: string, rating: int, snippet: string, location: string}> $samples */
        $samples = [];
        /** @var array<string, bool> $newLocations distinct location names with new reviews */
        $newLocations = [];
        /** @var array<int, bool> $newLocationIds distinct location ids with new reviews (for location-scoped routing) */
        $newLocationIds = [];
        $firstReviewId = null; // for the single-review deep-link in the digest
        $newReviewIds = []; // every new review id, for the multi-review filtered deep-link

        /** @var list<array{name: string, count: int, rating: ?float}> $firstSynced locations whose FIRST import finished this run */
        $firstSynced = [];

        try {
            $locations = Location::query()->get();
            $stats['locations'] = $locations->count();

            foreach ($locations as $location) {
                $accountId = $location->zernio_account_id ?: 'fake-account';

                // A location that has never synced is about to backfill its whole
                // review history — don't email about hundreds of "new" reviews.
                $firstSyncForLocation = $location->last_synced_at === null;

                try {
                    $reviews = $provider->listReviews($accountId, $location->external_id);
                } catch (Throwable $e) {
                    // e.g. expired Google token, or Zernio still backfilling a
                    // freshly connected location — skip it, don't abort, and
                    // surface the error in the Locations table.
                    Log::warning('ReviewSync: location reviews failed', [
                        'location' => $location->external_id,
                        'error' => $e->getMessage(),
                    ]);
                    $location->forceFill(['last_sync_error' => Str::limit($e->getMessage(), 500)])->save();
                    $stats['errors']++;

                    continue;
                }

                // Bulk-load all existing reviews for this batch to avoid N+1 queries.
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
                        'review_link' => $rd->reviewLink,
                        'created_at_external' => $rd->createdAtExternal,
                        'synced_at' => now(),
                    ];

                    // Reply reconciliation: take the platform's reply when present;
                    // otherwise preserve a locally-authored reply by leaving the
                    // reply_* columns untouched.
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
                    $stats['reviews']++;

                    // Collect a sample for the digest email — but never on a
                    // location's first sync (that's a backfill, not new activity).
                    if ($review->wasRecentlyCreated && ! $firstSyncForLocation) {
                        $newCount++;
                        $newLocations[$location->name] = true;
                        $newLocationIds[(int) $location->id] = true;
                        $firstReviewId ??= $review->id;
                        $newReviewIds[] = (int) $review->id;

                        $review->setRelation('location', $location);
                        app(WebhookDispatcher::class)
                            ->dispatch(WebhookEvents::REVIEW_CREATED, $review->toWebhookPayload());

                        if (count($samples) < 5) {
                            $samples[] = [
                                'author' => (string) ($review->author_name ?: '—'),
                                'rating' => (int) $review->rating,
                                // Original (untranslated) text — strips the "(Translated by Google)…" wrapper.
                                'snippet' => Str::limit((string) ($review->originalText() ?? $review->text), 120),
                                'location' => (string) $location->name,
                            ];
                        }
                    }
                }

                // Single aggregate query instead of two separate count/avg queries.
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
                    $firstSynced[] = [
                        'name' => (string) $location->name,
                        'count' => (int) $agg->total,
                        'rating' => $agg->avg_rating !== null ? round((float) $agg->avg_rating, 1) : null,
                    ];
                }
            }

        } finally {
            if ($previous !== null) {
                tenancy()->initialize($previous);
            } else {
                tenancy()->end();
            }
        }

        // "Your reviews are in" — ONE email per run covering every location
        // whose first import finished (the backfill itself is never digested).
        if ($firstSynced !== []) {
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
                Log::warning('Location synced email failed', [
                    'workspace' => $workspace->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Best-effort digest email AFTER tenant context is restored — never let
        // a mail failure fail the whole sync.
        if ($newCount > 0) {
            $locationLabel = $newLocations !== [] ? implode(', ', array_keys($newLocations)) : $workspace->name;
            // Deep-link so the button lands on exactly the new reviews:
            //  - one review  → ?review={id}  opens its reply panel,
            //  - many reviews → ?reviews={ids} filters the list to just them.
            $base = rtrim((string) config('app.url'), '/').'/reviews';
            $reviewsUrl = match (true) {
                $newCount === 1 && $firstReviewId !== null => $base.'?review='.$firstReviewId,
                $newReviewIds !== [] => $base.'?reviews='.implode(',', array_slice($newReviewIds, 0, 50)),
                default => $base,
            };

            // When the digest is about a single location, route it to that
            // location's people; a multi-location digest goes to everyone.
            $digestLocationId = count($newLocationIds) === 1 ? (int) array_key_first($newLocationIds) : null;

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
                    locationId: $digestLocationId,
                );
            } catch (Throwable $e) {
                Log::warning('New reviews email failed', [
                    'workspace' => $workspace->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Slack / Telegram (each channel is best-effort internally).
            ChatChannels::send(
                $workspace,
                NotificationCategory::REPUTATION,
                'new_reviews',
                ['count' => $newCount, 'location' => $locationLabel, 'url' => $reviewsUrl],
            );
        }

        return $stats;
    }
}
