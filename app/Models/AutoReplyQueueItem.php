<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — an AI-generated reply and its lifecycle.
 */
class AutoReplyQueueItem extends Model
{
    protected $table = 'auto_reply_queue';

    protected $fillable = [
        'review_id',
        'generated_text',
        'status',
        'mode',
        'model',
        'ai_agent_id',
        'credits_spent',
        'error',
        'decided_by',
        'decided_at',
        'post_at',
    ];

    protected $casts = [
        'credits_spent' => 'integer',
        'decided_at' => 'datetime',
        'post_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    /** Scheduled replies whose post time has arrived. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')->where('post_at', '<=', now());
    }
}
