<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems\Pages;

use App\Filament\App\Resources\AutoReplyQueueItems\AutoReplyQueueItemResource;
use Filament\Resources\Pages\ListRecords;

class ListAutoReplyQueueItems extends ListRecords
{
    protected static string $resource = AutoReplyQueueItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
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
