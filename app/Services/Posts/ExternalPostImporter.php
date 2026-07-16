<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\Location;
use App\Models\Post;
use App\Services\Zernio\ZernioRestClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Imports previously-published Google local posts back into the app. Zernio
 * exposes them as read-only "external" posts (published directly on the
 * platform, not through us), so each becomes a published Post tagged
 * origin=imported: it shows on the calendar and can be viewed or duplicated as
 * a draft, but not edited or deleted (Google owns the original).
 *
 * Must run inside an initialized tenancy.
 */
class ExternalPostImporter
{
    /** Safety cap on pages walked per account (~12 months of history). */
    private const MAX_PAGES = 50;

    public function __construct(private ZernioRestClient $zernio) {}

    /**
     * Sync every connected location's external posts.
     *
     * @return array{locations: int, imported: int}
     */
    public function import(): array
    {
        if (! $this->zernio->configured()) {
            return ['locations' => 0, 'imported' => 0];
        }

        $locations = 0;
        $imported = 0;

        Location::query()
            ->whereNotNull('zernio_account_id')
            ->get()
            ->each(function (Location $location) use (&$locations, &$imported): void {
                $accountId = trim((string) $location->zernio_account_id);
                if ($accountId === '') {
                    return;
                }

                $locations++;

                // Kick an on-demand refresh so recent posts are available now,
                // then walk the stored history. Best-effort: a sync failure
                // still lets us read whatever Zernio already holds.
                try {
                    $this->zernio->syncExternalPosts($accountId);
                } catch (Throwable $e) {
                    Log::info('External post on-demand sync skipped', ['account' => $accountId, 'error' => $e->getMessage()]);
                }

                $imported += $this->importAccount($location, $accountId);
            });

        return ['locations' => $locations, 'imported' => $imported];
    }

    private function importAccount(Location $location, string $accountId): int
    {
        $stored = 0;
        $page = 1;
        $pages = 1;

        do {
            try {
                $result = $this->zernio->listExternalPosts($accountId, $page);
            } catch (Throwable $e) {
                Log::warning('External post listing failed', ['account' => $accountId, 'page' => $page, 'error' => $e->getMessage()]);
                break;
            }

            foreach ($result['posts'] as $post) {
                $post = (array) $post;
                $stored += $this->store($location, [
                    'platform_post_id' => (string) ($post['platformPostId'] ?? ''),
                    'content' => (string) ($post['content'] ?? ''),
                    'image_url' => $post['mediaUrl'] ?? ($post['thumbnailUrl'] ?? null),
                    'url' => $post['platformPostUrl'] ?? null,
                    'published_at' => $post['publishedAt'] ?? null,
                ]) ? 1 : 0;
            }

            $pages = (int) ($result['pagination']['pages'] ?? 1);
            $page++;
        } while ($page <= $pages && $page <= self::MAX_PAGES);

        return $stored;
    }

    /**
     * Store one external post as an imported, published Post. Deduped on the
     * Google-native id so both backfill and webhook delivery are idempotent.
     * Shared by the listing walk and the post.external.created webhook.
     *
     * @param  array{platform_post_id?: string, content?: ?string, image_url?: ?string, url?: ?string, published_at?: ?string}  $data
     */
    public function store(Location $location, array $data): bool
    {
        $platformPostId = trim((string) ($data['platform_post_id'] ?? ''));
        if ($platformPostId === '') {
            return false;
        }

        if (Post::query()->where('platform_post_id', $platformPostId)->exists()) {
            return false;
        }

        $publishedAt = filled($data['published_at'] ?? null)
            ? CarbonImmutable::parse((string) $data['published_at'])
            : CarbonImmutable::now();

        Post::create([
            'type' => 'update',
            'caption' => (string) ($data['content'] ?? ''),
            'image_url' => $data['image_url'] ?? null,
            'cta_url' => $data['url'] ?? null,
            'location_ids' => [$location->id],
            'status' => 'published',
            'scheduled_at' => $publishedAt,
            'origin' => 'imported',
            'platform_post_id' => $platformPostId,
            'created_by_name' => 'Google',
        ]);

        return true;
    }
}
