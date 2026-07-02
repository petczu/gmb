<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Review;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get the full detail of a single review by its id, including the original and Google-translated text and the current reply.')]
class GetReviewTool extends Tool
{
    public function handle(Request $request): Response
    {
        $review = Review::query()->with('location')->find((int) $request->get('id'));

        if ($review === null) {
            return Response::error('Review not found in this workspace.');
        }

        return Response::text((string) json_encode([
            'id' => $review->id,
            'author' => $review->author_name,
            'rating' => $review->rating,
            'text' => $review->originalText(),
            'text_translated' => $review->translatedText(),
            'reply' => $review->reply_text,
            'reply_status' => $review->reply_status,
            'replied_at' => $review->replied_at?->toIso8601String(),
            'location' => $review->location?->name,
            'date' => $review->created_at_external?->toIso8601String(),
            'link' => $review->review_link,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The review id (from list_reviews).')->required(),
        ];
    }
}
