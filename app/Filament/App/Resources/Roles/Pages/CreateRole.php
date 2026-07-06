<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Pages;

use App\Filament\App\Resources\Roles\RolePermissions;
use App\Filament\App\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @var list<string> */
    protected array $selectedPermissions = [];

    /** Normalize + stamp the guard; team_id is set by spatie from the current team. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPermissions = RolePermissions::extract($data);

        $data['name'] = str($data['name'])->lower()->slug('_')->value();
        $data['guard_name'] = 'web';

        return array_diff_key($data, array_flip(RolePermissions::formKeys()));
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }
}
