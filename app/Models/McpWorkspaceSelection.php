<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The workspace an MCP OAuth connection is bound to, keyed by (user, OAuth
 * client). Written on the consent screen, read by ResolveMcpWorkspace.
 *
 * @property int $user_id
 * @property string $oauth_client_id
 * @property string $workspace_id
 */
class McpWorkspaceSelection extends Model
{
    protected $fillable = [
        'user_id',
        'oauth_client_id',
        'workspace_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
