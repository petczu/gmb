<?php

namespace App\Filament\App\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable()
                    ->sortable(),

                TextColumn::make('address')
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
                    ->color(fn (Location $record): string => $record->last_synced_at === null ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?string $state, Location $record): string => $record->last_synced_at === null
                        ? __('resources/locations.syncing')
                        : $record->last_synced_at->diffForHumans())
                    ->placeholder(__('resources/locations.syncing'))
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->recordActions([
                Action::make('disconnect')
                    ->label(__('resources/locations.disconnect'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('resources/locations.disconnect_heading'))
                    ->modalDescription(__('resources/locations.disconnect_desc'))
                    ->action(function (Location $record): void {
                        $record->reviews()->delete();
                        $record->delete();

                        // Reflect the lower location count on the subscription.
                        $workspace = \App\Models\Workspace::find(session('current_workspace_id'));
                        if ($workspace !== null) {
                            app(\App\Services\Billing\LocationBilling::class)->syncQuantity($workspace);
                        }

                        Notification::make()->title(__('resources/locations.disconnected'))->success()->send();
                    }),
            ]);
    }
}
