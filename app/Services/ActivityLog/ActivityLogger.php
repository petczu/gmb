<?php

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\Models\ActivityEntry;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Appends entries to the tenant's activity feed. Best-effort: a failed write
 * must never break the action being logged, and calls outside an initialized
 * tenancy are silently dropped.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function log(string $action, array $meta = [], ?Model $subject = null): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        try {
            ActivityEntry::create([
                'user_id' => auth()->id(),
                'user_name' => auth()->user()?->name,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'meta' => $meta === [] ? null : $meta,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Feed writes are non-critical (e.g. tenant not yet migrated).
        }
    }
}
