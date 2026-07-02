<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('resources/roles.section'))->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(60)
                    ->helperText(__('resources/roles.name_helper'))
                    ->disabled(fn ($record): bool => $record?->name === 'owner'),

                CheckboxList::make('permissions')
                    ->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => Str::headline($record->name))
                    ->columns(2)
                    ->bulkToggleable()
                    ->helperText(__('resources/roles.permissions_helper')),
            ]),
        ]);
    }
}
