<?php

namespace App\Filament\App\Resources\Automations\Pages;

use App\Filament\App\Resources\Automations\AutomationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomation extends CreateRecord
{
    protected static string $resource = AutomationResource::class;

    // Single "Create" button — drop "Create & create another".
    protected static bool $canCreateAnother = false;

    /** Back to the automations list after creating (instead of the edit page). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        AutomationResource::notifyOverlaps($this->record);
    }
}
