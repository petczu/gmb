<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems\Pages;

use App\Filament\App\Resources\AutoReplyQueueItems\AutoReplyQueueItemResource;
use App\Models\AutoReplyQueueItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListAutoReplyQueueItems extends ListRecords
{
    protected static string $resource = AutoReplyQueueItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Drop the default tabs block that renders ABOVE the table — the tabs are
     * rendered inside the table's own header slot instead (see the table's
     * ->header()). The tab STATE (activeTab + getTabs) still comes from the
     * HasTabs trait, so the query filtering is unchanged.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
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
