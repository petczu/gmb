<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — one row per star group (optionally per location).
 */
class AutoReplyRule extends Model
{
    protected $fillable = [
        'location_id',
        'rating',
        'enabled',
        'mode',
        'tone',
        'instruction',
        'language',
    ];

    protected $casts = [
        'rating' => 'integer',
        'enabled' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
