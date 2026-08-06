<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Livewire\DatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The bell panel, scoped to the active workspace. A member can belong to several
 * workspaces, so notifications are tagged with data->workspace_id at dispatch
 * ([[NotificationDispatcher]]); here we show only the current workspace's.
 * Legacy rows with no workspace tag are hidden: showing them in every
 * workspace read as a scoping bug, and their action URLs predate the
 * current domain anyway.
 */
class ScopedDatabaseNotifications extends DatabaseNotifications
{
    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        $workspaceId = session('current_workspace_id');
        if ($workspaceId !== null) {
            $query->where('data->workspace_id', (string) $workspaceId);
        }

        return $query;
    }
}
