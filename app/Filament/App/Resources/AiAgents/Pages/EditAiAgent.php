<?php

namespace App\Filament\App\Resources\AiAgents\Pages;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Filament\App\Resources\AiAgents\Tables\AiAgentsTable;
use App\Models\AiAgent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiAgent extends EditRecord
{
    protected static string $resource = AiAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AiAgentsTable::testAction()
                ->record(fn (): AiAgent => $this->record),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Enforce a single default agent.
        if ($this->record->is_default) {
            $this->record->makeDefault();
        }
    }
}
