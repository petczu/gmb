<?php

namespace App\Filament\App\Resources\Locations\Tables;

use App\Filament\App\Pages\BusinessProfile;
use App\Models\Location;
use App\Models\LocationGroup;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Billing\LocationBilling;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reviews_count', 'desc')
            // Refresh while a freshly connected location is still syncing in the
            // background so reviews/rating appear without a manual reload.
            ->poll('5s')
            ->searchable(Location::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedMapPin)
            ->emptyStateHeading(__('resources/locations.empty_heading'))
            ->emptyStateDescription(__('resources/locations.empty_desc'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('resources/locations.empty_cta'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => route('zernio.google.connect')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources/locations.col_location'))
                    ->limit(32)
                    ->tooltip(fn (Location $record): ?string => mb_strlen((string) $record->name) > 32 ? $record->name : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
                    ->limit(45)
                    ->tooltip(fn (Location $record): ?string => mb_strlen((string) $record->address) > 45 ? $record->address : null)
                    ->toggleable()
                    ->searchable()
                    ->visibleFrom('lg'),

                TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? $state.' ★' : '—')
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state >= 4.0 => 'success',
                        (float) $state >= 3.0 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('reviews_count')
                    ->label(__('resources/locations.col_reviews'))
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active', 'connected' => 'success',
                        'pending', 'syncing' => 'warning',
                        'error', 'disconnected', 'suspended' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('last_synced_at')
                    ->label(__('resources/locations.col_last_synced'))
                    ->badge(fn (Location $record): bool => $record->last_synced_at === null)
                    ->color(fn (Location $record): string => match (true) {
                        $record->last_synced_at !== null => 'gray',
                        filled($record->last_sync_error) => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state, Location $record): string => match (true) {
                        $record->last_synced_at !== null => $record->last_synced_at->diffForHumans(),
                        filled($record->last_sync_error) => __('resources/locations.sync_failed'),
                        default => __('resources/locations.syncing'),
                    })
                    // Failure → the provider's error message; first import still
                    // running → a "give it a few minutes" hint.
                    ->tooltip(fn (Location $record): ?string => $record->last_sync_error
                        ?? ($record->last_synced_at === null ? __('resources/locations.syncing_hint') : null))
                    ->placeholder(__('resources/locations.syncing'))
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                // Organize the list by group: pick one to show only its members.
                SelectFilter::make('group')
                    ->label(__('resources/locations.group'))
                    ->options(fn (): array => LocationGroup::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function ($query, array $data) {
                        $group = filled($data['value'] ?? null) ? LocationGroup::find($data['value']) : null;

                        return $group === null ? $query : $query->whereIn('id', $group->locationIds());
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('editInfo')
                        ->label(__('resources/locations.edit_info'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => auth()->user()?->can('edit_business_info') ?? false)
                        ->url(fn (Location $record): string => BusinessProfile::getUrl().'?location='.$record->id),

                    Action::make('setTimezone')
                        ->label(__('resources/locations.set_timezone'))
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->visible(fn (): bool => auth()->user()?->can('edit_business_info') ?? false)
                        ->modalHeading(__('resources/locations.timezone_heading'))
                        ->fillForm(fn (Location $record): array => ['timezone' => $record->timezone])
                        ->schema([
                            Select::make('timezone')
                                ->label(__('resources/locations.timezone'))
                                ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn (string $tz): array => [$tz => $tz])->all())
                                ->searchable()
                                ->native(false)
                                ->helperText(__('resources/locations.timezone_helper')),
                        ])
                        ->action(function (Location $record, array $data): void {
                            $record->forceFill(['timezone' => filled($data['timezone'] ?? null) ? $data['timezone'] : null])->save();
                            Notification::make()->title(__('resources/locations.timezone_saved'))->success()->send();
                        }),

                    Action::make('disconnect')
                        ->label(__('resources/locations.disconnect'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('resources/locations.disconnect_heading'))
                        ->modalDescription(__('resources/locations.disconnect_desc'))
                        ->action(function (Location $record): void {
                            ActivityLogger::log('location.disconnected', ['location' => $record->name]);
                            $record->reviews()->delete();
                            $record->delete();

                            // Reflect the lower location count on the subscription.
                            $workspace = Workspace::find(session('current_workspace_id'));
                            if ($workspace !== null) {
                                app(LocationBilling::class)->syncQuantity($workspace);
                            }

                            Notification::make()->title(__('resources/locations.disconnected'))->success()->send();
                        }),
                ]),
            ])
            ->toolbarActions([]);
    }
}
