<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Tables;

use App\Filament\App\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(Role::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck)
            ->emptyStateHeading(__('resources/roles.empty_heading'))
            ->emptyStateDescription(__('resources/roles.empty_desc'))
            ->emptyStateActions([
                // Only link to the create screen when custom roles are actually
                // creatable (a Pro capability) — otherwise it dead-ends in a 403.
                Action::make('create')
                    ->label(__('resources/roles.empty_cta'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => RoleResource::canCreate())
                    ->url(fn (): string => RoleResource::getUrl('create')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'success',
                        'admin' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label(__('resources/roles.col_permissions'))
                    ->counts('permissions')
                    ->badge()
                    ->visibleFrom('sm'),

                TextColumn::make('users_count')
                    ->label(__('resources/roles.col_members'))
                    ->counts('users')
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                // Owner is implicit "can do everything", not editable or deletable.
                EditAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== 'owner'),
                DeleteAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== 'owner'),
            ]);
    }
}
