<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — one daily rating/review-count snapshot of a competitor.
 * Google's Places API has no history, so trends are built from these rows
 * (written by competitors:refresh and when a competitor is added).
 */
class CompetitorSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['competitor_id', 'day', 'rating', 'reviews_count'];

    protected $casts = [
        'day' => 'date',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
    ];
}
