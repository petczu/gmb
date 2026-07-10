<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a saved Ask-AI conversation for one user in this workspace.
 * Messages are a JSON array of {role, content}; the title is derived from the
 * first question.
 */
class AiConversation extends Model
{
    protected $fillable = ['user_id', 'title', 'messages', 'last_message_at'];

    protected $casts = [
        'messages' => 'array',
        'last_message_at' => 'datetime',
    ];
}
