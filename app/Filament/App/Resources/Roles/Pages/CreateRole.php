<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Roles\Pages;

use App\Filament\App\Pages\Billing;
use App\Filament\App\Resources\Roles\RolePermissions;
use App\Filament\App\Resources\Roles\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @var list<string> */
    protected array $selectedPermissions = [];

    /**
     * Custom roles are a Pro capability. A team manager landing here on a
     * lower plan is sent to Billing with an upgrade hint instead of
     * Filament's bare 403 page.
     */
    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->can('manage_roles') ?? false, 403);

        if (RoleResource::canCreate()) {
            return;
        }

        Notification::make()
            ->title(__('resources/roles.pro_locked'))
            ->body(__('resources/roles.pro_locked_body'))
            ->warning()
            ->send();

        // redirect() inside a Livewire request returns Livewire's Redirector,
        // not a Symfony response, so build the RedirectResponse directly.
        throw new HttpResponseException(new RedirectResponse(Billing::getUrl()));
    }

    /** Back to the roles list after creating (instead of the edit page). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

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
