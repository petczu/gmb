<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — a review-reply automation (Flow + Content).
 */
class Automation extends Model
{
    protected $fillable = [
        'name',
        'enabled',
        'trigger',
        'rating_filter',
        'all_locations',
        'location_ids',
        'respect_working_hours',
        'reply_delay_min_minutes',
        'reply_delay_max_minutes',
        'working_hours',
        'reply_to_previous',
        'approve_before_posting',
        'content_type',
        'default_message',
        'ai_agent_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'rating_filter' => 'array',
        'all_locations' => 'boolean',
        'location_ids' => 'array',
        'respect_working_hours' => 'boolean',
        'reply_delay_min_minutes' => 'integer',
        'reply_delay_max_minutes' => 'integer',
        'working_hours' => 'array',
        'reply_to_previous' => 'boolean',
        'approve_before_posting' => 'boolean',
    ];

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    /**
     * Does this automation apply to the given review?
     */
    public function matches(Review $review): bool
    {
        if (! empty($this->rating_filter) && ! in_array((int) $review->rating, array_map('intval', $this->rating_filter), true)) {
            return false;
        }

        if (! $this->all_locations && ! in_array($review->location_id, array_map('intval', $this->location_ids ?? []), true)) {
            return false;
        }

        return true;
    }
}
