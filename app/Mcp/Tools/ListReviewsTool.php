<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Review;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List reviews for the workspace, newest first, with optional filters (rating, replied, text, location, date range) and pagination.')]
class ListReviewsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $query = Review::query()->with('location');

        if (($rating = $request->get('rating')) !== null) {
            $query->where('rating', (int) $rating);
        }
        if (($replied = $request->get('replied')) !== null) {
            $replied ? $query->whereNotNull('reply_text') : $query->whereNull('reply_text');
        }
        if (($hasText = $request->get('has_text')) !== null) {
            $hasText
                ? $query->whereNotNull('text')->where('text', '!=', '')
                : $query->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''));
        }
        if ($locationId = $request->get('location_id')) {
            $query->where('location_id', (int) $locationId);
        }
        if ($from = $request->get('from')) {
            $query->where('created_at_external', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->where('created_at_external', '<=', $to.' 23:59:59');
        }

        $total = (clone $query)->count();
        $limit = max(1, min(100, (int) ($request->get('limit') ?? 20)));
        $offset = max(0, (int) ($request->get('offset') ?? 0));

        $reviews = $query->orderByDesc('created_at_external')->offset($offset)->limit($limit)->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'author' => $review->author_name,
                'rating' => $review->rating,
                'text' => $review->originalText(),
                'reply' => $review->reply_text,
                'replied_at' => $review->replied_at?->toIso8601String(),
                'location' => $review->location?->name,
                'date' => $review->created_at_external?->toIso8601String(),
            ])->all();

        return Response::text((string) json_encode([
            'total' => $total,
            'returned' => count($reviews),
            'offset' => $offset,
            'reviews' => $reviews,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'rating' => $schema->integer()->description('Filter by star rating (1-5).'),
            'replied' => $schema->boolean()->description('true = only reviews we replied to; false = only without a reply.'),
            'has_text' => $schema->boolean()->description('true = only reviews with written text; false = rating-only reviews.'),
            'location_id' => $schema->integer()->description('Filter to a single location id (see list_locations).'),
            'from' => $schema->string()->description('Earliest review date, YYYY-MM-DD.'),
            'to' => $schema->string()->description('Latest review date, YYYY-MM-DD.'),
            'limit' => $schema->integer()->description('Max rows to return (default 20, max 100).'),
            'offset' => $schema->integer()->description('Pagination offset (default 0).'),
        ];
    }
}
