<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TENANT model — an internal collaboration comment on a calendar post, with
 * optional file attachments and @-mentions of workspace members.
 *
 * @property int $post_id
 * @property ?int $user_id
 * @property ?string $user_name
 * @property string $body
 * @property ?array<int, string> $attachments stored file paths on the uploads disk
 * @property ?array<int, int> $mentioned_user_ids
 */
class PostComment extends Model
{
    protected $fillable = [
        'post_id', 'user_id', 'user_name', 'body', 'attachments', 'mentioned_user_ids',
    ];

    protected $casts = [
        'attachments' => 'array',
        'mentioned_user_ids' => 'array',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
