<?php

declare(strict_types=1);

namespace App\Services\Competitors;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * DataForSEO Google Reviews client: pulls individual reviews (with exact
 * timestamps) for a place so we can build per-day history. Task-based standard
 * queue by default (cheapest, ~$0.00075/10 reviews); post a task, then poll
 * task_get until the results are ready.
 *
 * Only used by the reviews backfill — the daily rating/review-count snapshot
 * stays on the cheaper my_business_info lookup (DataForSeoClient).
 */
class DataForSeoReviewsClient
{
    protected const BASE = 'https://api.dataforseo.com/v3/business_data/google/reviews';

    public function configured(): bool
    {
        return (bool) config('services.dataforseo.reviews_enabled')
            && filled(config('services.dataforseo.login'))
            && filled(config('services.dataforseo.password'));
    }

    /** Create a reviews task; returns its id. */
    public function postTask(string $placeId, ?int $depth = null): string
    {
        $depth = $this->normalizeDepth($depth);
        $priority = config('services.dataforseo.reviews_priority') ? 2 : 1;

        $response = $this->request()
            ->post(self::BASE.'/task_post', [[
                'place_id' => $placeId,
                'location_code' => 2840,
                'language_code' => 'en',
                'sort_by' => 'newest',
                'depth' => $depth,
                'priority' => $priority,
            ]])
            ->throw()
            ->json();

        $task = $response['tasks'][0] ?? [];
        $id = $task['id'] ?? null;

        if ((int) ($task['status_code'] ?? 0) !== 20100 || ! is_string($id)) {
            throw new \RuntimeException(sprintf(
                'DataForSEO reviews task_post failed (%s): %s',
                $task['status_code'] ?? 'no status',
                $task['status_message'] ?? 'no message',
            ));
        }

        return $id;
    }

    /**
     * Poll one task. Returns the normalized reviews when ready, or null while
     * it is still queued/in progress.
     *
     * @return list<array{review_id: string, rating: ?float, reviewed_at: ?CarbonImmutable, author: ?string, text: ?string, language: ?string}>|null
     */
    public function getTask(string $taskId): ?array
    {
        // Business Data reviews task_get has NO "/advanced" variant (unlike
        // SERP endpoints) — the plain path is the only one, else it 404s.
        $response = $this->request()
            ->get(self::BASE.'/task_get/'.$taskId)
            ->throw()
            ->json();

        $task = $response['tasks'][0] ?? [];
        $status = (int) ($task['status_code'] ?? 0);

        // 40602 = "Task In Queue", 40601 = "Task Handed"; not an error, retry.
        if (in_array($status, [40601, 40602], true)) {
            return null;
        }

        if ($status !== 20000) {
            throw new \RuntimeException(sprintf(
                'DataForSEO reviews task_get failed (%s): %s',
                $task['status_code'] ?? 'no status',
                $task['status_message'] ?? 'no message',
            ));
        }

        $items = $task['result'][0]['items'] ?? [];

        return collect(is_array($items) ? $items : [])
            ->filter(fn ($item): bool => is_array($item))
            ->map(fn (array $item): array => $this->normalize($item))
            ->filter(fn (array $r): bool => $r['review_id'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{review_id: string, rating: ?float, reviewed_at: ?CarbonImmutable, author: ?string, text: ?string, language: ?string}
     */
    private function normalize(array $item): array
    {
        $timestamp = $item['timestamp'] ?? null;

        return [
            'review_id' => (string) ($item['review_id'] ?? ''),
            'rating' => isset($item['rating']['value']) ? (float) $item['rating']['value'] : null,
            'reviewed_at' => is_string($timestamp) && $timestamp !== '' ? CarbonImmutable::parse($timestamp) : null,
            'author' => isset($item['profile_name']) ? (string) $item['profile_name'] : null,
            'text' => isset($item['review_text']) ? (string) $item['review_text'] : null,
            'language' => isset($item['language']) ? (string) $item['language'] : null,
        ];
    }

    private function normalizeDepth(?int $depth): int
    {
        $depth ??= (int) config('services.dataforseo.reviews_depth', 700);

        // Multiples of ten; Google exposes at most 4490.
        return (int) max(10, min(4490, (int) (ceil($depth / 10) * 10)));
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth(
            (string) config('services.dataforseo.login'),
            (string) config('services.dataforseo.password'),
        )
            ->acceptJson()
            ->timeout(30)
            ->connectTimeout(5);
    }
}
