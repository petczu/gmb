<?php

namespace App\Filament\App\Resources\Locations\Pages;

use App\Filament\App\Resources\Locations\HoursBulkEdit;
use App\Filament\App\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Models\LocationGroup;
use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use App\Services\Reviews\ZernioConnectionManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        $isZernio = config('services.reviews.driver') === 'zernio';

        return [
            // Bulk hours editing across locations, now or from a future date.
            Action::make('editHours')
                ->label(__('resources/locations.bulk_hours'))
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->visible(fn (): bool => Location::query()->exists()
                    && (auth()->user()?->can('edit_business_info') ?? false))
                ->modalHeading(__('resources/locations.bulk_hours_heading'))
                ->modalDescription(__('resources/locations.bulk_hours_desc'))
                ->modalSubmitActionLabel(__('resources/locations.bulk_hours_submit'))
                ->schema(HoursBulkEdit::schema())
                ->action(fn (array $data) => HoursBulkEdit::apply($data)),

            // Create a group: name a cluster of locations. A location belongs to
            // one group at a time; the group tags its members in the list and is
            // selectable in the dashboard/report location filter. Manage a group
            // per row (ungroup) like the Competitors page.
            Action::make('createGroup')
                ->label(__('resources/locations.create_group'))
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->color('gray')
                ->visible(fn (): bool => Location::query()->count() >= 2)
                ->modalHeading(__('resources/locations.group_heading'))
                ->modalSubmitActionLabel(__('resources/locations.group_create'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('resources/locations.group_name'))
                        ->required()
                        ->maxLength(60),
                    Select::make('location_ids')
                        ->label(__('resources/locations.group_locations'))
                        ->multiple()
                        ->required()
                        ->minItems(2)
                        ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText(__('resources/locations.group_locations_helper')),
                ])
                ->action(fn (array $data) => $this->createGroup($data)),

            // OAuth connect, pick which location(s) to track in the picker.
            Action::make('connect')
                ->label(__('resources/locations.add_location'))
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                // Hidden while empty so only the centered empty-state button shows.
                ->visible(fn (): bool => $isZernio && Location::query()->exists())
                // Resolving the connect URL can take 10-15s; dim + block the
                // button on click so the wait reads as "working".
                ->extraAttributes(['x-on:click' => "\$el.style.opacity='.7';\$el.style.pointerEvents='none';"])
                ->url(fn (): string => route('zernio.google.connect')),

            // Dev convenience without a key: seed two demo locations + reviews.
            Action::make('addDemo')
                ->label(__('resources/locations.add_demo_data'))
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => ! $isZernio && Location::query()->doesntExist())
                ->action(function (): void {
                    $workspace = $this->workspace();
                    app(ZernioConnectionManager::class)->link($workspace, 'fake-account', 'Demo account (fake)');

                    Location::updateOrCreate(['external_id' => 'loc-downtown'], [
                        'zernio_account_id' => 'fake-account',
                        'name' => 'Acme Coffee, Downtown',
                        'address' => '12 Market St, Springfield',
                        'status' => 'active',
                    ]);
                    Location::updateOrCreate(['external_id' => 'loc-harbor'], [
                        'zernio_account_id' => 'fake-account',
                        'name' => 'Acme Coffee, Harbor',
                        'address' => '88 Pier Ave, Springfield',
                        'status' => 'active',
                    ]);

                    app(ReviewSync::class)->syncWorkspace($workspace);
                    Notification::make()->title(__('resources/locations.demo_added'))->success()->send();
                }),
        ];
    }

    /**
     * Create one named group from the chosen locations. A location belongs to a
     * single group, so the members are first detached from any group they were
     * already in (emptied groups are removed), mirroring the Competitors page.
     *
     * @param  array<string, mixed>  $data
     */
    private function createGroup(array $data): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        $ids = array_values(array_filter(array_map('intval', (array) ($data['location_ids'] ?? []))));

        if ($name === '' || count($ids) < 2) {
            Notification::make()->title(__('resources/locations.group_need_two'))->warning()->send();

            return;
        }

        foreach ($ids as $id) {
            LocationGroup::detachLocation($id);
        }

        LocationGroup::create(['name' => $name, 'location_ids' => $ids]);

        Notification::make()->title(__('resources/locations.group_created'))->success()->send();
    }

    private function workspace(): Workspace
    {
        return Workspace::findOrFail(session('current_workspace_id'));
    }
}
