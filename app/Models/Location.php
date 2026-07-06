<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TENANT model — lives in the per-workspace DB. Uses the default connection,
 * which stancl swaps to the tenant DB while tenancy is initialized. No pinned
 * connection, and user references (if any) carry no FK (users are central).
 */
class Location extends Model
{
    protected $fillable = [
        'external_id',
        'source_id',
        'listing_data',
        'zernio_account_id',
        'place_id',
        'name',
        'address',
        'phone',
        'website_url',
        'status',
        'is_verified',
        'rating',
        'reviews_count',
        'review_goal',
        'last_synced_at',
    ];

    protected $casts = [
        'listing_data' => 'array',
        'is_verified' => 'boolean',
        'rating' => 'decimal:1',
        'reviews_count' => 'integer',
        'review_goal' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
