<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * TENANT model — an internal collaboration comment on a calendar post, with
 * optional file attachments, @-mentions of workspace members, threaded
 * replies (parent_id) and emoji reactions.
 *
 * @property int $post_id
 * @property ?int $parent_id
 * @property ?int $user_id
 * @property ?string $user_name
 * @property string $body
 * @property ?array<int, string> $attachments stored file paths on the uploads disk
 * @property ?array<int, int> $mentioned_user_ids
 * @property ?array<int, array{emoji: string, user_id: int, user_name: string}> $reactions
 * @property ?Carbon $edited_at
 */
class PostComment extends Model
{
    /** The reaction emoji offered in the picker. */
    public const REACTION_EMOJI = ['👍', '❤️', '😂', '🎉', '👀', '🙏', '✅', '🔥'];

    protected $fillable = [
        'post_id', 'parent_id', 'user_id', 'user_name', 'body', 'attachments', 'mentioned_user_ids', 'reactions', 'edited_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'mentioned_user_ids' => 'array',
        'reactions' => 'array',
        'edited_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Reactions grouped for display: emoji => list of reactor names, with a
     * flag for the given user.
     *
     * @return array<string, array{names: list<string>, mine: bool}>
     */
    public function groupedReactions(?int $forUserId): array
    {
        $grouped = [];

        foreach ($this->reactions ?? [] as $reaction) {
            $emoji = (string) ($reaction['emoji'] ?? '');
            if ($emoji === '') {
                continue;
            }

            $grouped[$emoji] ??= ['names' => [], 'mine' => false];
            $grouped[$emoji]['names'][] = (string) ($reaction['user_name'] ?? '?');

            if ($forUserId !== null && (int) ($reaction['user_id'] ?? 0) === $forUserId) {
                $grouped[$emoji]['mine'] = true;
            }
        }

        return $grouped;
    }
}
