<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Pages;

use App\Filament\App\Pages\Billing;
use App\Filament\App\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('resources/roles.new_role')),

            // Custom roles are a Pro capability: instead of the create button
            // silently disappearing on lower plans, show a locked one that
            // explains itself and leads to the upgrade.
            Action::make('newRoleLocked')
                ->label(__('resources/roles.new_role'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('gray')
                ->visible(fn (): bool => ! RoleResource::canCreate())
                ->tooltip(__('resources/roles.pro_locked'))
                ->url(Billing::getUrl()),
        ];
    }
}
