<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CENTRAL model — a connected Google Business account for a workspace.
 * Pinned to the central connection. No token: all Zernio calls use the single
 * platform key in config('services.reviews.zernio_key').
 */
class GoogleAccount extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'workspace_id',
        'zernio_account_id',
        'name',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
