<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The bell panel, scoped to the active workspace. A member can belong to several
 * workspaces, so notifications are tagged with data->workspace_id at dispatch
 * ([[NotificationDispatcher]]); here we show only the current workspace's, plus
 * any legacy ones with no workspace tag (so nothing pre-existing disappears).
 */
class ScopedDatabaseNotifications extends DatabaseNotifications
{
    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        $workspaceId = session('current_workspace_id');
        if ($workspaceId !== null) {
            $query->where(fn (Builder $q): Builder => $q
                ->where('data->workspace_id', (string) $workspaceId)
                ->orWhereNull('data->workspace_id'));
        }

        return $query;
    }
}
