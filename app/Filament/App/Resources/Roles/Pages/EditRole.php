<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Pages;

use App\Filament\App\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Owner is implicit "can do everything", never editable.
        abort_if($this->record->name === 'owner', 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn (): bool => $this->record->name !== 'owner'),
        ];
    }
}
