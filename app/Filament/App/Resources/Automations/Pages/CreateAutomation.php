<?php

namespace App\Filament\App\Resources\Automations\Pages;

use App\Filament\App\Resources\Automations\AutomationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomation extends CreateRecord
{
    protected static string $resource = AutomationResource::class;

    // Single "Create" button — drop "Create & create another".
    protected static bool $canCreateAnother = false;
}
