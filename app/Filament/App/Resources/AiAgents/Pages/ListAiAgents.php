<?php

namespace App\Filament\App\Resources\AiAgents\Pages;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Models\AiAgent;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAiAgents extends ListRecords
{
    protected static string $resource = AiAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Workspace-wide reply rules: applied on top of EVERY agent, so a
            // style correction ("say 'Raum', never 'Room' inside a German
            // sentence") lands once instead of being copied into each agent.
            Action::make('sharedRules')
                ->label(__('resources/ai_agents.shared_rules'))
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('gray')
                ->modalHeading(__('resources/ai_agents.shared_rules_heading'))
                ->modalDescription(__('resources/ai_agents.shared_rules_desc'))
                ->modalSubmitActionLabel(__('resources/ai_agents.shared_rules_save'))
                ->schema([
                    Textarea::make('reply_guidelines')
                        ->hiddenLabel()
                        ->rows(8)
                        ->maxLength(2000)
                        ->placeholder(__('resources/ai_agents.shared_rules_placeholder'))
                        ->default(fn (): string => AiAgent::sharedRules()),
                ])
                ->action(function (array $data): void {
                    $workspace = Workspace::find((string) session('current_workspace_id'));
                    $workspace?->setAttribute('reply_guidelines', trim((string) ($data['reply_guidelines'] ?? '')));
                    $workspace?->save();

                    Notification::make()->title(__('resources/ai_agents.shared_rules_saved'))->success()->send();
                }),

            // Hidden while empty so only the centered empty-state button shows.
            CreateAction::make()->visible(fn (): bool => AiAgent::query()->exists()),
        ];
    }
}
