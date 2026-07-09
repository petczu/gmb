<?php

namespace App\Filament\App\Resources\AiAgents\Pages;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Models\AiAgent;
use Filament\Resources\Pages\CreateRecord;

class CreateAiAgent extends CreateRecord
{
    protected static string $resource = AiAgentResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        // First agent becomes default automatically; otherwise honor the toggle.
        if ($this->record->is_default || AiAgent::query()->count() === 1) {
            $this->record->makeDefault();
        }
    }
}
