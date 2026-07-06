<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Schemas;

use App\Support\PermissionCatalog;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $groups = [];

        foreach (PermissionCatalog::groups() as $group => $permissions) {
            $groups[] = CheckboxList::make('perms_'.$group)
                ->label(__('resources/roles.group_'.$group))
                ->options(collect($permissions)->mapWithKeys(
                    fn (string $perm): array => [$perm => __('resources/roles.perm_'.$perm)],
                )->all())
                ->descriptions(collect($permissions)->mapWithKeys(
                    fn (string $perm): array => [$perm => __('resources/roles.perm_'.$perm.'_desc')],
                )->all())
                ->bulkToggleable()
                // Mapped to role permissions by the Create/Edit pages via
                // syncPermissions — form state only, not a model attribute.
                ->dehydrated(true);
        }

        // One full-width column: Role card on top, Permissions below
        // (Filament's default two-column form grid is disabled).
        return $schema->columns(1)->components([
            Section::make(__('resources/roles.section'))->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(60)
                    ->helperText(__('resources/roles.name_helper'))
                    ->disabled(fn ($record): bool => $record?->name === 'owner'),
            ]),

            Section::make(__('resources/roles.permissions_section'))
                ->description(__('resources/roles.permissions_helper'))
                ->schema($groups),
        ]);
    }
}
