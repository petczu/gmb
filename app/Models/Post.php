<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a Google Business Profile post published (or scheduled)
 * through Zernio's content publishing API. Scheduling is handled by Zernio
 * (scheduledOn), so rows here are a local history, not a delivery queue.
 *
 * @property string $type
 * @property ?string $caption
 * @property list<int> $location_ids
 * @property list<string> $source_ids
 * @property string $status
 */
class Post extends Model
{
    public const TYPES = ['update', 'offer', 'event', 'photo'];

    public const CTA_TYPES = ['book', 'order', 'shop', 'learn_more', 'sign_up', 'call'];

    public const PHOTO_CATEGORIES = [
        'ADDITIONAL', 'EXTERIOR', 'INTERIOR', 'PRODUCT', 'AT_WORK',
        'FOOD_AND_DRINK', 'MENU', 'COMMON_AREA', 'ROOMS', 'TEAMS', 'CUSTOMER',
    ];

    protected $fillable = [
        'type', 'caption', 'title', 'cta_type', 'cta_url', 'image_url',
        'photo_category', 'starts_at', 'ends_at', 'voucher_code', 'redeem_url',
        'terms_url', 'location_ids', 'source_ids', 'scheduled_at', 'status',
        'external_ids', 'error', 'created_by', 'created_by_name',
    ];

    protected $casts = [
        'location_ids' => 'array',
        'source_ids' => 'array',
        'external_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];
}
