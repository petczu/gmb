<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Jobs\RunReviewAutomation;
use App\Mail\AccountDisconnectedMail;
use App\Mail\NegativeReviewMail;
use App\Mail\SyncRestoredMail;
use App\Models\GoogleAccount;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Posts\ExternalPostImporter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Applies a decoded Zernio webhook payload: upserts reviews, reconciles reply
 * state (a published reply, or a reply deleted on the platform), and flips
 * connected-account status. A genuinely new unanswered review also dispatches
 * RunReviewAutomation (queued) so the matching automation replies within
 * seconds instead of waiting for the scheduled pass.
 *
 * Review/Location rows live in the per-workspace TENANT DB, so review events
 * initialize tenancy for the owning workspace and tear it down afterwards.
 * Account status lives on the CENTRAL google_accounts table (no tenant).
 */
class ZernioWebhookHandler
{
    public function handle(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');

        switch ($event) {
            case 'review.new':
            case 'review.updated':
                $this->handleReview($payload, $event);
                break;

            case 'account.connected':
                $this->updateAccountStatus($payload, 'connected');
                break;

            case 'account.disconnected':
                $this->updateAccountStatus($payload, 'revoked');
                break;

            case 'post.external.created':
            case 'post.external.updated':
                $this->handleExternalPost($payload);
                break;

            case 'webhook.test':
                Log::info('Zernio webhook: test event received');
                break;

            default:
                Log::info('Zernio webhook: unhandled event', ['event' => $event]);
                break;
        }
    }

    /**
     * Ingest / reconcile a single review inside the owning workspace's tenant DB.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleReview(array $payload, string $event): void
    {
        $accountId = $payload['account']['id'] ?? null;
        if ($accountId === null) {
            return;
        }

        $account = GoogleAccount::query()->where('zernio_account_id', $accountId)->first();
        if ($account === null) {
            // Untracked account — nothing to ingest into.
            return;
        }

        $workspace = $account->workspace;
        if ($workspace === null) {
            return;
        }

        $review = $payload['review'] ?? [];
        if (! is_array($review)) {
            return;
        }

        [$locationExternalId, $reviewExternalId] = $this->parseReviewId((string) ($review['id'] ?? ''));
        if ($reviewExternalId === '') {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $location = Location::query()->where('external_id', $locationExternalId)->first();
            if ($location === null) {
                // Untracked location — the workspace is not tracking it.
                return;
            }

            $hasReply = (bool) ($review['hasReply'] ?? false);
            $reply = $review['reply'] ?? null;

            $existing = Review::query()->where('external_review_id', $reviewExternalId)->first();

            $attributes = [
                'location_id' => $location->id,
                'author_name' => $review['reviewer']['name'] ?? null,
                'rating' => isset($review['rating']) ? (int) $review['rating'] : null,
                'text' => $review['text'] ?? null,
                'created_at_external' => $this->parseDate($review['createdAt'] ?? null),
                'synced_at' => now(),
            ];

            if ($hasReply && is_array($reply)) {
                // A reply exists on the platform — record it. Keep an existing
                // reply_source (e.g. ai_auto/ai_draft) if we already set one;
                // otherwise treat it as a manual platform reply.
                $attributes['reply_text'] = $reply['text'] ?? null;
                $attributes['replied_at'] = $this->parseDate($reply['createdAt'] ?? null);
                $attributes['reply_status'] = 'published';
                $attributes['reply_source'] = $existing?->reply_source ?? 'manual';
            } else {
                // No reply on the platform — confirms a reply was deleted there.
                $attributes['reply_text'] = null;
                $attributes['replied_at'] = null;
                $attributes['reply_status'] = null;
                $attributes['reply_source'] = null;
            }

            $stored = Review::query()->updateOrCreate(
                ['external_review_id' => $reviewExternalId],
                $attributes,
            );

            // A genuinely new, unanswered review → run the matching automation
            // right away (queued; the scheduled pass is only the safety net).
            if ($event === 'review.new' && $existing === null && $attributes['reply_text'] === null) {
                RunReviewAutomation::dispatch($workspace->id, (int) $stored->id);
            }

            // Alert the owner about a brand-new low-rating review. Strictly:
            // a genuinely new review (review.new + not previously stored) with
            // rating <= 2. Best-effort — never let a mail failure break ingest.
            $rating = $attributes['rating'];
            if ($event === 'review.new' && $existing === null && $rating !== null && $rating <= 2) {
                $this->notifyNegativeReview($workspace, $location, $review, $rating, (int) $stored->id);
            }
        } finally {
            if ($previous !== null) {
                tenancy()->initialize($previous);
            } else {
                tenancy()->end();
            }
        }
    }

    /**
     * Best-effort: email the workspace owner about a new low-rating review.
     *
     * @param  array<string, mixed>  $review
     */
    private function notifyNegativeReview(Workspace $workspace, Location $location, array $review, int $rating, int $reviewId): void
    {
        try {
            $businessName = $location->name ?? $workspace->name;
            $authorName = (string) ($review['reviewer']['name'] ?? 'A customer');
            $snippet = Str::limit((string) ($review['text'] ?? ''), 160);
            // Deep link: ?review={id} opens the reply slide-over (ListReviews).
            $reviewsUrl = rtrim((string) config('app.url'), '/').'/reviews?review='.$reviewId;

            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::REPUTATION,
                fn (string $name, string $lang) => new NegativeReviewMail(
                    name: $name,
                    businessName: $businessName,
                    authorName: $authorName,
                    rating: $rating,
                    snippet: $snippet,
                    reviewsUrl: $reviewsUrl,
                    lang: $lang,
                ),
                locationId: (int) $location->id,
            );
        } catch (Throwable $e) {
            Log::warning('Negative review email failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ingest a natively-published Google post (post.external.created/updated)
     * into the owning workspace as an imported, published Post so it appears on
     * the calendar. Deduped on the platform id, so an update event is a no-op
     * once the post is already stored.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleExternalPost(array $payload): void
    {
        $accountId = $payload['account']['id'] ?? null;
        if ($accountId === null) {
            return;
        }

        $account = GoogleAccount::query()->where('zernio_account_id', $accountId)->first();
        $workspace = $account?->workspace;
        if ($workspace === null) {
            return;
        }

        $post = $payload['post'] ?? [];
        if (! is_array($post) || trim((string) ($post['id'] ?? '')) === '') {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $location = Location::query()->where('zernio_account_id', $accountId)->first();
            if ($location === null) {
                return;
            }

            $mediaItems = is_array($post['mediaItems'] ?? null) ? $post['mediaItems'] : [];

            app(ExternalPostImporter::class)->store($location, [
                'platform_post_id' => (string) $post['id'],
                'content' => (string) ($post['content'] ?? ''),
                'image_url' => $post['thumbnailUrl'] ?? ($mediaItems[0]['url'] ?? null),
                'url' => $post['url'] ?? null,
                'published_at' => $post['publishedAt'] ?? null,
            ]);
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }

    /**
     * Flip the central connected-account status (no tenant context needed) and,
     * on a meaningful transition, notify the workspace owner.
     *
     * Status update always happens when the value actually changes; the owner
     * email is strictly best-effort (a mail failure never breaks the webhook).
     *
     * @param  array<string, mixed>  $payload
     */
    private function updateAccountStatus(array $payload, string $status): void
    {
        $accountId = $payload['account']['id'] ?? null;
        if ($accountId === null) {
            return;
        }

        $account = GoogleAccount::query()->where('zernio_account_id', $accountId)->first();
        if ($account === null) {
            return;
        }

        $previousStatus = (string) $account->status;

        if ($previousStatus === $status) {
            // No transition — nothing to update or notify about.
            return;
        }

        $account->forceFill(['status' => $status])->save();

        // connected -> revoked: the account just dropped. Notify once per transition.
        if ($status === 'revoked' && $previousStatus !== 'revoked') {
            $this->notifyAccountDisconnected($account);
        }

        // bad-state -> connected: a recovery (NOT a first-time connect). Notify.
        if ($status === 'connected' && in_array($previousStatus, ['revoked', 'error'], true)) {
            $this->notifySyncRestored($account);
        }
    }

    /** Best-effort: email the account's workspace owner that the connection dropped. */
    private function notifyAccountDisconnected(GoogleAccount $account): void
    {
        try {
            $workspace = $account->workspace;
            if ($workspace === null) {
                return;
            }

            $accountName = $account->name ?? 'your Google account';
            $locationsUrl = rtrim((string) config('app.url'), '/').'/locations';

            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new AccountDisconnectedMail(
                    name: $name,
                    accountName: $accountName,
                    locationsUrl: $locationsUrl,
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Account disconnected email failed', [
                'account' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Best-effort: email the account's workspace owner that syncing resumed. */
    private function notifySyncRestored(GoogleAccount $account): void
    {
        try {
            $workspace = $account->workspace;
            if ($workspace === null) {
                return;
            }

            $accountName = $account->name ?? 'your Google account';
            $dashboardUrl = rtrim((string) config('app.url'), '/').'/locations';

            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new SyncRestoredMail(
                    name: $name,
                    accountName: $accountName,
                    dashboardUrl: $dashboardUrl,
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Sync restored email failed', [
                'account' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The webhook review.id is a FULL platform path like
     * `accounts/123/locations/456/reviews/789`, but our stored ids are bare:
     * Location.external_id is the bare location id, Review.external_review_id
     * the bare review id. Extract both; if the markers are absent, fall back to
     * using the raw value as the review id.
     *
     * @return array{0: string, 1: string} [locationExternalId, reviewExternalId]
     */
    private function parseReviewId(string $id): array
    {
        if (preg_match('#locations/([^/]+)/reviews/([^/]+)#', $id, $m) === 1) {
            return [$m[1], $m[2]];
        }

        return ['', $id];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
