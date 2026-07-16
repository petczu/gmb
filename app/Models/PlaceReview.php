<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — one individual Google review of a place, pulled from
 * DataForSEO and shared across all workspaces (keyed by public place_id).
 *
 * Author name and text are third-party personal data: stored for future
 * per-day analytics/sentiment but NOT surfaced in the UI until the
 * competitor-insights add-on is legally cleared.
 */
class PlaceReview extends Model
{
    /** Central table — pinned so tenancy never swaps the connection. */
    protected $connection = 'mysql';

    protected $fillable = [
        'place_id',
        'review_id',
        'rating',
        'reviewed_at',
        'author',
        'text',
        'language',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'reviewed_at' => 'datetime',
    ];
}
