<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Review;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only review listing for the Ask AI agent (mirrors the MCP
 * ListReviewsTool). Runs inside the current tenant.
 */
class ListReviews implements Tool
{
    public function description(): Stringable|string
    {
        return 'List reviews, newest first, with optional filters: rating (1-5), replied (bool), has_text (bool), location_id, from/to dates (YYYY-MM-DD), limit (max 50).';
    }

    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();
        $query = Review::query()->with('location');

        if (($rating = ($args['rating'] ?? null)) !== null) {
            $query->where('rating', (int) $rating);
        }
        if (($replied = ($args['replied'] ?? null)) !== null) {
            $replied ? $query->whereNotNull('reply_text') : $query->whereNull('reply_text');
        }
        if (($hasText = ($args['has_text'] ?? null)) !== null) {
            $hasText
                ? $query->whereNotNull('text')->where('text', '!=', '')
                : $query->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''));
        }
        if ($locationId = ($args['location_id'] ?? null)) {
            $query->where('location_id', (int) $locationId);
        }
        if ($from = ($args['from'] ?? null)) {
            $query->where('created_at_external', '>=', $from);
        }
        if ($to = ($args['to'] ?? null)) {
            $query->where('created_at_external', '<=', $to.' 23:59:59');
        }

        $limit = max(1, min(50, (int) ($args['limit'] ?? 20)));

        $reviews = $query->orderByDesc('created_at_external')->limit($limit)->get()
            ->map(fn (Review $r): array => [
                'id' => $r->id,
                'author' => $r->author_name,
                'rating' => $r->rating,
                'text' => $r->originalText(),
                'reply' => $r->reply_text,
                'location' => $r->location?->name,
                'date' => $r->created_at_external?->toDateString(),
            ]);

        return (string) json_encode($reviews, JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'rating' => $schema->integer()->min(1)->max(5),
            'replied' => $schema->boolean(),
            'has_text' => $schema->boolean(),
            'location_id' => $schema->integer(),
            'from' => $schema->string(),
            'to' => $schema->string(),
            'limit' => $schema->integer(),
        ];
    }
}
