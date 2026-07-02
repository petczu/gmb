<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Aggregate review stats for the workspace: total, average rating, star distribution, reply rate, rating-only count and new reviews this month. Optional location/date filters.')]
class ReviewStatsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $base = Review::query();

        if ($locationId = $request->get('location_id')) {
            $base->where('location_id', (int) $locationId);
        }
        if ($from = $request->get('from')) {
            $base->where('created_at_external', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $base->where('created_at_external', '<=', $to.' 23:59:59');
        }

        $total = (clone $base)->count();

        $distribution = [];
        foreach (range(1, 5) as $stars) {
            $distribution[$stars] = (clone $base)->where('rating', $stars)->count();
        }

        $replied = (clone $base)->whereNotNull('reply_text')->count();
        $ratingOnly = (clone $base)->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''))->count();
        $newThisMonth = (clone $base)->where('created_at_external', '>=', CarbonImmutable::now()->startOfMonth())->count();

        return Response::text((string) json_encode([
            'total' => $total,
            'average_rating' => $total > 0 ? round((float) (clone $base)->avg('rating'), 2) : null,
            'distribution' => $distribution,
            'replied' => $replied,
            'reply_rate_percent' => $total > 0 ? (int) round($replied / $total * 100) : 0,
            'rating_only' => $ratingOnly,
            'new_this_month' => $newThisMonth,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'location_id' => $schema->integer()->description('Limit stats to a single location id.'),
            'from' => $schema->string()->description('Earliest review date, YYYY-MM-DD.'),
            'to' => $schema->string()->description('Latest review date, YYYY-MM-DD.'),
        ];
    }
}
