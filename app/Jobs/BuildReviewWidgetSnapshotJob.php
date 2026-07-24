<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Review;
use App\Models\ReviewWidget;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Rebuilds a widget's tenancy-free snapshot: enters the workspace tenancy, reads
 * the selected reviews per the widget filters, and writes a normalised copy back
 * onto the CENTRAL review_widgets row so the public embed never touches the
 * tenant DB.
 */
class BuildReviewWidgetSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $widgetId) {}

    public function handle(): void
    {
        $widget = ReviewWidget::query()->find($this->widgetId);

        if ($widget === null) {
            return;
        }

        $workspace = Workspace::query()->find($widget->workspace_id);

        if ($workspace === null) {
            return;
        }

        $previous = tenant();
        tenancy()->initialize($workspace);

        try {
            $snapshot = $this->build($widget);
        } finally {
            $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
        }

        // The widget model is pinned to the central connection, so this writes
        // to the central DB even though it runs inside the tenant context.
        $widget->forceFill([
            'snapshot' => $snapshot,
            'refreshed_at' => now(),
        ])->save();
    }

    /**
     * @return array{reviews: array<int, array<string, mixed>>, summary: array{average: float, count: int}, generated_at: string}
     */
    private function build(ReviewWidget $widget): array
    {
        $locationIds = (array) $widget->setting('location_ids', []);
        $minRating = (int) $widget->setting('min_rating', 4);
        $requireText = (bool) $widget->setting('require_text', true);
        $hidden = array_map('intval', (array) $widget->setting('hidden_ids', []));
        $pinned = array_map('intval', (array) $widget->setting('pinned_ids', []));
        $max = max(1, (int) $widget->setting('max_reviews', 12));
        $sort = (string) $widget->setting('sort', 'newest');

        $base = Review::query()
            ->with('location:id,name')
            ->when($locationIds !== [], fn ($q) => $q->whereIn('location_id', $locationIds))
            ->where('rating', '>=', $minRating)
            ->when($requireText, fn ($q) => $q->whereNotNull('text')->where('text', '!=', ''))
            ->whereNotIn('id', $hidden);

        // Header aggregate: the true business rating over the selected scope,
        // independent of the card-level rating/text filters.
        $summaryScope = Review::query()
            ->when($locationIds !== [], fn ($q) => $q->whereIn('location_id', $locationIds));
        $count = (clone $summaryScope)->count();
        $average = $count > 0 ? round((float) (clone $summaryScope)->avg('rating'), 1) : 0.0;

        $ordered = (clone $base);
        match ($sort) {
            'highest' => $ordered->orderByDesc('rating')->orderByDesc('created_at_external'),
            'random' => $ordered->inRandomOrder(),
            default => $ordered->orderByDesc('created_at_external'),
        };

        // Pinned reviews always come first, in the order they were pinned.
        $pinnedReviews = $pinned === [] ? collect() : (clone $base)->whereIn('id', $pinned)->get()
            ->sortBy(fn (Review $r) => array_search($r->id, $pinned, true))->values();

        $rest = $ordered->whereNotIn('id', $pinned)->limit(max(0, $max - $pinnedReviews->count()))->get();

        $reviews = $pinnedReviews->concat($rest)
            ->map(fn (Review $r): array => $this->normalize($r))
            ->values()
            ->all();

        return [
            'reviews' => $reviews,
            'summary' => ['average' => $average, 'count' => $count],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(Review $review): array
    {
        return [
            'id' => $review->id,
            'author' => $review->author_name ?: '',
            'rating' => (int) $review->rating,
            'text' => $review->originalText() ?? '',
            'reply' => $review->reply_text,
            'location' => $review->location?->name,
            'date_iso' => $review->created_at_external?->toIso8601String(),
            'date' => $review->created_at_external?->translatedFormat('d M Y'),
            'link' => $review->review_link,
        ];
    }
}
