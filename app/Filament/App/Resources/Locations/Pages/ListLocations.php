<?php

namespace App\Filament\App\Resources\Locations\Pages;

use App\Filament\App\Resources\Locations\HoursBulkEdit;
use App\Filament\App\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\Reviews\ReviewSync;
use App\Services\Reviews\ZernioConnectionManager;
use Filament\Actions\Action;
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

    private function workspace(): Workspace
    {
        return Workspace::findOrFail(session('current_workspace_id'));
    }
}
