<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Mail\PostFailedMail;
use App\Models\Location;
use App\Models\Post;
use App\Models\Workspace;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Zernio\ZernioRestClient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the Zernio POST /v1/posts payload from a Post and records the
 * outcome. Each target location becomes one platforms[] entry (same Zernio
 * account, different GBP locationId). Scheduling is Zernio-side.
 */
class PostPublisher
{
    public function __construct(protected ZernioRestClient $client) {}

    /**
     * @param  Collection<int, Location>  $locations
     */
    public function publish(Post $post, Collection $locations): void
    {
        try {
            $created = $this->client->createPost($this->payload($post, $locations));

            $status = $this->statusOf($created, $post);
            $error = $this->platformError($created);
            $post->forceFill([
                'status' => $status,
                'external_ids' => array_values(array_filter([(string) ($created['_id'] ?? $created['id'] ?? '')])),
                'error' => $error,
            ])->save();

            if ($status === 'failed') {
                $this->notifyFailed($post, $locations, $error);
            }
        } catch (RequestException $e) {
            $message = $this->errorMessage($e);
            $post->forceFill(['status' => 'failed', 'error' => $message])->save();
            $this->notifyFailed($post, $locations, $message);
        } catch (Throwable $e) {
            $post->forceFill(['status' => 'failed', 'error' => $e->getMessage()])->save();
            $this->notifyFailed($post, $locations, $e->getMessage());
        }
    }

    /**
     * Best-effort: email the Operations recipients that a post failed to
     * publish. Never throws — a notification failure must not mask the post
     * outcome.
     *
     * @param  Collection<int, Location>  $locations
     */
    protected function notifyFailed(Post $post, Collection $locations, ?string $reason): void
    {
        try {
            $workspace = tenant();
            if (! $workspace instanceof Workspace) {
                return;
            }

            $business = $locations->first()?->name ?? $workspace->name;
            $postsUrl = rtrim((string) config('app.url'), '/').'/posts';

            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang): PostFailedMail => new PostFailedMail(
                    name: $name,
                    businessName: (string) $business,
                    postsUrl: $postsUrl,
                    reason: filled($reason) ? Str::limit((string) $reason, 200) : null,
                    lang: $lang,
                ),
            );
        } catch (Throwable $e) {
            Log::warning('Post failed email failed', ['post' => $post->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return array<string, mixed>
     */
    public function payload(Post $post, Collection $locations): array
    {
        $payload = [
            'platforms' => $locations->map(fn (Location $location): array => [
                'platform' => 'googlebusiness',
                'accountId' => (string) $location->zernio_account_id,
                'platformSpecificData' => $this->platformData($post, $location),
            ])->values()->all(),
            'timezone' => 'UTC',
        ];

        if (filled($post->caption)) {
            $payload['content'] = $post->caption;
        }

        if (filled($post->image_url)) {
            $payload['mediaItems'] = [['type' => 'image', 'url' => $post->image_url]];
        }

        if ($post->scheduled_at !== null) {
            $payload['scheduledFor'] = $post->scheduled_at->clone()->utc()->toIso8601String();
        } else {
            $payload['publishNow'] = true;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function platformData(Post $post, Location $location): array
    {
        $data = [
            'locationId' => (string) $location->external_id,
            'topicType' => match ($post->type) {
                'event' => 'EVENT',
                'offer' => 'OFFER',
                default => 'STANDARD',
            },
        ];

        if (filled($post->cta_type) && $post->type !== 'offer') {
            $data['callToAction'] = array_filter([
                'type' => strtoupper((string) $post->cta_type),
                'url' => $post->cta_url,
            ]);
        }

        // Google carries the title + date range of BOTH event and offer posts
        // in the event object.
        if (in_array($post->type, ['event', 'offer'], true) && $post->starts_at !== null && $post->ends_at !== null) {
            $data['event'] = [
                'title' => (string) $post->title,
                'schedule' => [
                    'startDate' => $this->date($post->starts_at->toImmutable()),
                    'startTime' => $this->time($post->starts_at->toImmutable()),
                    'endDate' => $this->date($post->ends_at->toImmutable()),
                    'endTime' => $this->time($post->ends_at->toImmutable()),
                ],
            ];
        }

        if ($post->type === 'offer') {
            $offer = array_filter([
                'couponCode' => $post->voucher_code,
                'redeemOnlineUrl' => $post->redeem_url,
                'termsConditions' => $post->terms_url,
            ]);

            if ($offer !== []) {
                $data['offer'] = $offer;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $created
     */
    protected function statusOf(array $created, Post $post): string
    {
        return match ((string) ($created['status'] ?? '')) {
            'published' => 'published',
            'scheduled' => 'scheduled',
            'failed' => 'failed',
            default => $post->scheduled_at !== null ? 'scheduled' : 'in_progress',
        };
    }

    /**
     * First per-platform error, if any target failed.
     *
     * @param  array<string, mixed>  $created
     */
    protected function platformError(array $created): ?string
    {
        foreach ((array) ($created['platforms'] ?? []) as $platform) {
            if (($platform['status'] ?? null) === 'failed') {
                return (string) ($platform['error'] ?? 'Publishing failed for one of the locations.');
            }
        }

        return null;
    }

    protected function errorMessage(RequestException $e): string
    {
        $body = $e->response?->json();

        if (is_array($body)) {
            return (string) ($body['error'] ?? $body['message'] ?? $body['title'] ?? $e->getMessage());
        }

        return $e->getMessage();
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    protected function date(CarbonImmutable $moment): array
    {
        return ['year' => $moment->year, 'month' => $moment->month, 'day' => $moment->day];
    }

    /**
     * @return array{hours: int, minutes: int}
     */
    protected function time(CarbonImmutable $moment): array
    {
        return ['hours' => $moment->hour, 'minutes' => $moment->minute];
    }
}
