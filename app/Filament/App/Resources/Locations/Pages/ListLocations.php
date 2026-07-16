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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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

            // Location groups: name a cluster of locations, then filter the list
            // or the dashboard/report location filter by it. Create, rename,
            // re-scope and delete all in one modal.
            Action::make('groups')
                ->label(__('resources/locations.groups'))
                ->icon(Heroicon::OutlinedRectangleGroup)
                ->color('gray')
                ->visible(fn (): bool => Location::query()->exists())
                ->modalHeading(__('resources/locations.groups_heading'))
                ->modalDescription(__('resources/locations.groups_desc'))
                ->modalSubmitActionLabel(__('resources/locations.groups_save'))
                ->fillForm(fn (): array => ['groups' => LocationGroup::query()->orderBy('name')->get()
                    ->map(fn (LocationGroup $group): array => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'location_ids' => $group->locationIds(),
                    ])->all()])
                ->schema([
                    Repeater::make('groups')
                        ->hiddenLabel()
                        ->addActionLabel(__('resources/locations.group_add'))
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('name')
                                ->label(__('resources/locations.group_name'))
                                ->required()
                                ->maxLength(60),
                            Select::make('location_ids')
                                ->label(__('resources/locations.group_locations'))
                                ->multiple()
                                ->required()
                                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all()),
                        ]),
                ])
                ->action(fn (array $data) => $this->saveGroups($data)),

            // OAuth connect, pick which location(s) to track in the picker.
            Action::make('connect')
                ->label(__('resources/locations.add_location'))
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                // Hidden while empty so only the centered empty-state button shows.
                ->visible(fn (): bool => $isZernio && Location::query()->exists())
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
     * Reconcile the groups repeater against stored LocationGroups: update rows
     * that carry an id, create rows without one, and delete any group the user
     * removed from the list.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveGroups(array $data): void
    {
        $keptIds = [];

        foreach (($data['groups'] ?? []) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $ids = array_values(array_filter(array_map('intval', (array) ($row['location_ids'] ?? []))));

            $group = filled($row['id'] ?? null) ? LocationGroup::find($row['id']) : null;
            if ($group !== null) {
                $group->update(['name' => $name, 'location_ids' => $ids]);
            } else {
                $group = LocationGroup::create(['name' => $name, 'location_ids' => $ids]);
            }

            $keptIds[] = $group->id;
        }

        // Anything not present in the submitted list was removed by the user.
        LocationGroup::query()->whereNotIn('id', $keptIds ?: [0])->delete();

        Notification::make()->title(__('resources/locations.groups_saved'))->success()->send();
    }

    private function workspace(): Workspace
    {
        return Workspace::findOrFail(session('current_workspace_id'));
    }
}
