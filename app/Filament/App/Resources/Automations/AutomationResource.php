<?php

namespace App\Filament\App\Resources\Automations;

use App\Filament\App\Resources\Automations\Pages\CreateAutomation;
use App\Filament\App\Resources\Automations\Pages\EditAutomation;
use App\Filament\App\Resources\Automations\Pages\ListAutomations;
use App\Filament\App\Resources\Automations\Schemas\AutomationForm;
use App\Filament\App\Resources\Automations\Tables\AutomationsTable;
use App\Models\Automation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'automations';

    protected static ?string $recordTitleAttribute = 'name';

    // Isolation is at the DB level via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.automations');
    }

    public static function form(Schema $schema): Schema
    {
        return AutomationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AutomationsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_automations') ?? false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutomations::route('/'),
            'create' => CreateAutomation::route('/create'),
            'edit' => EditAutomation::route('/{record}/edit'),
        ];
    }

    /**
     * Soft warning after save when this automation competes with others for
     * the same reviews (locations AND ratings intersect). Overlap is allowed:
     * the runtime deterministically picks one winner (specific locations beat
     * "All locations", then the older automation), so we inform, not block.
     */
    public static function notifyOverlaps(Automation $automation): void
    {
        if (! $automation->enabled) {
            return;
        }

        $overlapping = $automation->overlapping();

        if ($overlapping->isEmpty()) {
            return;
        }

        $list = $overlapping
            ->map(fn (Automation $other): string => '"'.$other->name.'"')
            ->implode(', ');

        Notification::make()
            ->title(__('resources/automations.overlap_title'))
            ->body(__('resources/automations.overlap_body', ['list' => $list]))
            ->warning()
            ->persistent()
            ->send();
    }
}
