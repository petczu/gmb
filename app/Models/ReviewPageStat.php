<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Daily aggregated counter for a review page (views + per-target clicks).
 * CENTRAL, no per-visitor data.
 */
class ReviewPageStat extends Model
{
    protected $connection = 'mysql';

    public $timestamps = false;

    protected $fillable = ['review_page_id', 'day', 'metric', 'count'];

    protected $casts = [
        'day' => 'date',
        'count' => 'integer',
    ];
}
