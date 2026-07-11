<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Review;
use App\Services\Reviews\ReviewProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'replied' => ['nullable', 'boolean'],
            'has_text' => ['nullable', 'boolean'],
            'location_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = Review::query()->with('location');

        if (isset($validated['rating'])) {
            $query->where('rating', (int) $validated['rating']);
        }
        if ($request->has('replied')) {
            $request->boolean('replied')
                ? $query->whereNotNull('reply_text')
                : $query->whereNull('reply_text');
        }
        if ($request->has('has_text')) {
            $request->boolean('has_text')
                ? $query->whereNotNull('text')->where('text', '!=', '')
                : $query->where(fn ($q) => $q->whereNull('text')->orWhere('text', ''));
        }
        if (isset($validated['location_id'])) {
            $query->where('location_id', (int) $validated['location_id']);
        }
        if (isset($validated['from'])) {
            $query->where('created_at_external', '>=', $validated['from']);
        }
        if (isset($validated['to'])) {
            $query->where('created_at_external', '<=', $validated['to'].' 23:59:59');
        }

        $perPage = (int) ($validated['per_page'] ?? 20);

        return ReviewResource::collection(
            $query->orderByDesc('created_at_external')->paginate($perPage)
        );
    }

    public function show(int $review): JsonResponse|ReviewResource
    {
        $model = Review::query()->with('location')->find($review);

        if ($model === null) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        return new ReviewResource($model);
    }

    public function reply(Request $request, int $review): JsonResponse|ReviewResource
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'min:1', 'max:4096'],
        ]);

        $model = Review::query()->with('location')->find($review);

        if ($model === null) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        $accountId = $model->location?->zernio_account_id ?? 'fake-account';

        app(ReviewProviderFactory::class)->make()->reply($accountId, $model->external_review_id, $validated['reply'], $model->location?->external_id);

        // Setting reply_status to 'published' fires the reply.published webhook
        // via the Review model's updated hook.
        $model->forceFill([
            'reply_text' => $validated['reply'],
            'replied_at' => now(),
            'reply_status' => 'published',
            'reply_source' => 'api',
        ])->save();

        return new ReviewResource($model);
    }
}
