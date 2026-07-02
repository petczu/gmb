<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles;

use App\Filament\App\Resources\Roles\Pages\CreateRole;
use App\Filament\App\Resources\Roles\Pages\EditRole;
use App\Filament\App\Resources\Roles\Pages\ListRoles;
use App\Filament\App\Resources\Roles\Schemas\RoleForm;
use App\Filament\App\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 81;

    protected static ?string $slug = 'roles';

    protected static ?string $recordTitleAttribute = 'name';

    // DB-level tenant isolation; roles are central but team-scoped by query.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.roles');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_team') ?? false;
    }

    /** Creating custom roles is a Pro feature; default roles still manageable. */
    public static function canCreate(): bool
    {
        if (! (auth()->user()?->can('manage_team') ?? false)) {
            return false;
        }

        $workspace = \App\Models\Workspace::find(session('current_workspace_id'));

        return $workspace !== null
            && app(\App\Services\Billing\LocationBilling::class)->allows($workspace, \App\Billing\Plans::CUSTOM_ROLES);
    }

    /** Only the current workspace's roles. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', session('current_workspace_id'));
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
