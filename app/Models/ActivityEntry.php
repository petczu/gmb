<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row in the workspace's activity feed. Tenant-scoped and append-only:
 * entries are written by ActivityLogger and never updated.
 *
 * @property ?int $user_id
 * @property ?string $user_name
 * @property string $action
 * @property ?string $subject_type
 * @property ?int $subject_id
 * @property ?array<string, mixed> $meta
 */
class ActivityEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $fillable = ['user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'meta', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
