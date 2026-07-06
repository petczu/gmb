<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Location;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Read-only workspace data for the Ask AI agent (mirrors the MCP
 * ListLocationsTool). Runs inside the current tenant.
 */
class ListLocations implements Tool
{
    public function description(): Stringable|string
    {
        return 'List the workspace\'s connected business locations with their average rating, review count and monthly review goal.';
    }

    public function handle(Request $request): Stringable|string
    {
        $locations = Location::query()->orderBy('name')->get()->map(fn (Location $l): array => [
            'id' => $l->id,
            'name' => $l->name,
            'rating' => $l->rating,
            'reviews_count' => $l->reviews_count,
            'monthly_goal' => $l->review_goal,
        ]);

        return (string) json_encode($locations, JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
