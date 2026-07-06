<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Aggregate review analytics for the Ask AI agent (mirrors the MCP
 * ReviewStatsTool). Runs inside the current tenant.
 */
class ReviewStats implements Tool
{
    public function description(): Stringable|string
    {
        return 'Aggregate review stats: total, average rating, star distribution, reply rate, rating-only count, new this month. Optional filters: location_id, from/to dates (YYYY-MM-DD).';
    }

    public function handle(Request $request): Stringable|string
    {
        $args = $request->all();
        $base = Review::query();

        if ($locationId = ($args['location_id'] ?? null)) {
            $base->where('location_id', (int) $locationId);
        }
        if ($from = ($args['from'] ?? null)) {
            $base->where('created_at_external', '>=', $from);
        }
        if ($to = ($args['to'] ?? null)) {
            $base->where('created_at_external', '<=', $to.' 23:59:59');
        }

        $total = (clone $base)->count();

        $distribution = [];
        foreach (range(1, 5) as $stars) {
            $distribution[$stars] = (clone $base)->where('rating', $stars)->count();
        }

        $replied = (clone $base)->whereNotNull('reply_text')->count();

        return (string) json_encode([
            'total' => $total,
            'average_rating' => $total > 0 ? round((float) (clone $base)->avg('rating'), 2) : null,
            'distribution' => $distribution,
            'replied' => $replied,
            'reply_rate_percent' => $total > 0 ? (int) round($replied / $total * 100) : 0,
            'rating_only' => (clone $base)->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''))->count(),
            'new_this_month' => (clone $base)->where('created_at_external', '>=', CarbonImmutable::now()->startOfMonth())->count(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location_id' => $schema->integer(),
            'from' => $schema->string(),
            'to' => $schema->string(),
        ];
    }
}
