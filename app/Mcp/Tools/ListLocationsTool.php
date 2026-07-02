<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Location;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the workspace\'s connected business locations with their average rating, review count and monthly goal.')]
class ListLocationsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $locations = Location::query()->orderBy('name')->get()->map(fn (Location $location): array => [
            'id' => $location->id,
            'name' => $location->name,
            'address' => $location->address,
            'rating' => $location->rating,
            'reviews_count' => $location->reviews_count,
            'monthly_goal' => $location->review_goal,
        ])->all();

        return Response::text((string) json_encode([
            'count' => count($locations),
            'locations' => $locations,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
