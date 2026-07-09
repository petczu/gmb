<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — one daily rating/review-count snapshot of a Google place,
 * shared across all workspaces (public data). Google's Places API has no
 * history, so trends are built from these rows (written by
 * competitors:refresh and when a competitor / tracked place is added).
 */
class PlaceSnapshot extends Model
{
    /** Central table — pinned so tenancy never swaps the connection. */
    protected $connection = 'mysql';

    public $timestamps = false;

    protected $fillable = ['place_id', 'day', 'rating', 'reviews_count'];

    protected $casts = [
        'day' => 'date',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
    ];
}
