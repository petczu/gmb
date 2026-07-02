<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Location
 */
class LocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'monthly_goal' => $this->review_goal,
        ];
    }
}
