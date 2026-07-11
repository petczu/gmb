<?php

namespace App\Filament\App\Resources\AiAgents\Pages;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Models\AiAgent;
use Filament\Resources\Pages\CreateRecord;

class CreateAiAgent extends CreateRecord
{
    protected static string $resource = AiAgentResource::class;

    protected static bool $canCreateAnother = false;

    /** Back to the agents list after creating (instead of the edit page). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // First agent becomes default automatically; otherwise honor the toggle.
        if ($this->record->is_default || AiAgent::query()->count() === 1) {
            $this->record->makeDefault();
        }
    }
}
