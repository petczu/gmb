<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Pages;

use App\Filament\App\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** Normalize + stamp the guard; team_id is set by spatie from the current team. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = str($data['name'])->lower()->slug('_')->value();
        $data['guard_name'] = 'web';

        return $data;
    }
}
