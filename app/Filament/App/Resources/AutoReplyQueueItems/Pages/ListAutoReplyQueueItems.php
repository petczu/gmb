<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems\Pages;

use App\Filament\App\Resources\AutoReplyQueueItems\AutoReplyQueueItemResource;
use App\Models\AutoReplyQueueItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAutoReplyQueueItems extends ListRecords
{
    protected static string $resource = AutoReplyQueueItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Status tabs: "Needs approval" holds items waiting for a human, while
     * auto-published replies live under "Scheduled" until their organic post
     * time arrives, so the two flows are no longer mixed into one list.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $count = fn (string $status): int => AutoReplyQueueItem::query()->where('status', $status)->count();

        return [
            'pending' => Tab::make(__('resources/auto_reply.tab_pending'))
                ->badge($count('pending') ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')),

            'scheduled' => Tab::make(__('resources/auto_reply.status_scheduled'))
                ->badge($count('scheduled') ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'scheduled')),

            'published' => Tab::make(__('resources/auto_reply.status_published'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'published')),

            'failed' => Tab::make(__('resources/auto_reply.status_failed'))
                ->badge($count('failed') ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'failed')),

            'all' => Tab::make(__('resources/auto_reply.tab_all')),
        ];
    }

    /**
     * Keep the column headers visible even when the Approvals table is empty
     * (an empty approvals inbox is a normal recurring state). The marker class
     * opts this page out of the global "hide headers when empty" rule.
     *
     * @return array<int, string>
     */
    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'fi-keep-column-headers'];
    }
}
