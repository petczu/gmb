<?php

namespace App\Filament\App\Resources\AiAgents\Pages;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Models\AiAgent;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiAgents extends ListRecords
{
    protected static string $resource = AiAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden while empty so only the centered empty-state button shows.
            CreateAction::make()->visible(fn (): bool => AiAgent::query()->exists()),
        ];
    }
}
