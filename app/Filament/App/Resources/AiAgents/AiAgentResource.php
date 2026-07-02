<?php

namespace App\Filament\App\Resources\AiAgents;

use App\Filament\App\Resources\AiAgents\Pages\CreateAiAgent;
use App\Filament\App\Resources\AiAgents\Pages\EditAiAgent;
use App\Filament\App\Resources\AiAgents\Pages\ListAiAgents;
use App\Filament\App\Resources\AiAgents\Schemas\AiAgentForm;
use App\Filament\App\Resources\AiAgents\Tables\AiAgentsTable;
use App\Models\AiAgent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiAgentResource extends Resource
{
    protected static ?string $model = AiAgent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'ai-agents';

    protected static ?string $recordTitleAttribute = 'name';

    // Isolation is at the DB level via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.ai_agents');
    }

    public static function getModelLabel(): string
    {
        return __('nav.ai_agent_model');
    }

    public static function form(Schema $schema): Schema
    {
        return AiAgentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiAgentsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_automations') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiAgents::route('/'),
            'create' => CreateAiAgent::route('/create'),
            'edit' => EditAiAgent::route('/{record}/edit'),
        ];
    }
}
