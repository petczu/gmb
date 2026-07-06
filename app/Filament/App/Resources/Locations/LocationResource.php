<?php

namespace App\Filament\App\Resources\Locations;

use App\Filament\App\Resources\Locations\Pages\ListLocations;
use App\Filament\App\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'locations';

    protected static ?string $recordTitleAttribute = 'name';

    // Isolation is at the DB level via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.locations');
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // locations come from the connected Google account, via sync
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_locations') ?? false;
    }

    /** Restrict to the user's allowed locations (null = all). */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $ids = auth()->user()?->allowedLocationIds((string) session('current_workspace_id'));

        return $ids === null ? $query : $query->whereKey($ids);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
        ];
    }
}
