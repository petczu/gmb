<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Review;
use App\Models\Workspace;
use App\Services\Reviews\ReviewProviderFactory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Publish a public reply to a review on Google. Permanent. Only available when the workspace has enabled MCP write access.')]
class ReplyToReviewTool extends Tool
{
    /** Registered only when the workspace opted into MCP write access. */
    public function shouldRegister(): bool
    {
        $workspace = tenant();

        return $workspace instanceof Workspace && $workspace->mcpWriteEnabled();
    }

    public function handle(Request $request): Response
    {
        $review = Review::query()->with('location')->find((int) $request->get('review_id'));

        if ($review === null) {
            return Response::error('Review not found in this workspace.');
        }

        $text = trim((string) $request->get('reply'));
        if ($text === '') {
            return Response::error('Reply text is required.');
        }

        $accountId = $review->location?->zernio_account_id ?? 'fake-account';

        app(ReviewProviderFactory::class)->make()->reply($accountId, $review->external_review_id, $text, $review->location?->external_id);

        $review->forceFill([
            'reply_text' => $text,
            'replied_at' => now(),
            'reply_status' => 'published',
            'reply_source' => 'mcp',
        ])->save();

        return Response::text((string) json_encode([
            'status' => 'published',
            'review_id' => $review->id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'review_id' => $schema->integer()->description('The review id to reply to (from list_reviews).')->required(),
            'reply' => $schema->string()->description('The reply text to publish publicly on Google.')->required(),
        ];
    }
}
