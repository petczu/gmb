<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $base = Review::query();

        if (isset($validated['location_id'])) {
            $base->where('location_id', (int) $validated['location_id']);
        }
        if (isset($validated['from'])) {
            $base->where('created_at_external', '>=', $validated['from']);
        }
        if (isset($validated['to'])) {
            $base->where('created_at_external', '<=', $validated['to'].' 23:59:59');
        }

        $total = (clone $base)->count();

        $distribution = [];
        foreach (range(1, 5) as $stars) {
            $distribution[$stars] = (clone $base)->where('rating', $stars)->count();
        }

        $replied = (clone $base)->whereNotNull('reply_text')->count();
        $ratingOnly = (clone $base)->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''))->count();
        $newThisMonth = (clone $base)->where('created_at_external', '>=', CarbonImmutable::now()->startOfMonth())->count();

        return response()->json([
            'total' => $total,
            'average_rating' => $total > 0 ? round((float) (clone $base)->avg('rating'), 2) : null,
            'distribution' => $distribution,
            'replied' => $replied,
            'reply_rate_percent' => $total > 0 ? (int) round($replied / $total * 100) : 0,
            'rating_only' => $ratingOnly,
            'new_this_month' => $newThisMonth,
        ]);
    }
}
