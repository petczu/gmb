<?php

namespace App\Filament\App\Resources\Automations\Pages;

use App\Filament\App\Resources\Automations\AutomationResource;
use App\Models\Automation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomations extends ListRecords
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden while empty so only the centered empty-state button shows.
            CreateAction::make()->visible(fn (): bool => Automation::query()->exists()),
        ];
    }
}
