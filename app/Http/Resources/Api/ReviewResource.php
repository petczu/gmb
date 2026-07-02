<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'location' => $this->whenLoaded('location', fn () => $this->location?->name),
            'author' => $this->author_name,
            'rating' => $this->rating,
            'text' => $this->originalText(),
            'text_translated' => $this->translatedText(),
            'reply' => $this->reply_text,
            'reply_status' => $this->reply_status,
            'reply_source' => $this->reply_source,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'review_link' => $this->review_link,
            'date' => $this->created_at_external?->toIso8601String(),
        ];
    }
}
