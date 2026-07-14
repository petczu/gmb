<?php

namespace App\Filament\App\Resources\Locations\Tables;

use App\Filament\App\Pages\BusinessProfile;
use App\Models\ExternalCalendarEvent;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Billing\LocationBilling;
use App\Services\Listings\ListingUpdater;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

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
            ->recordActions([
                ActionGroup::make([
                    Action::make('editInfo')
                        ->label(__('resources/locations.edit_info'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => auth()->user()?->can('edit_business_info') ?? false)
                        ->url(fn (Location $record): string => BusinessProfile::getUrl().'?location='.$record->id),

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
            ->toolbarActions([
                self::bulkEditHours(),
            ]);
    }

    /**
     * Set regular and/or special opening hours on every selected location in
     * one go (e.g. holiday closures across the whole chain).
     */
    protected static function bulkEditHours(): BulkAction
    {
        return BulkAction::make('editHours')
            ->label(__('resources/locations.bulk_hours'))
            ->icon(Heroicon::OutlinedClock)
            ->visible(fn (): bool => auth()->user()?->can('edit_business_info') ?? false)
            ->modalHeading(__('resources/locations.bulk_hours_heading'))
            ->modalDescription(__('resources/locations.bulk_hours_desc'))
            ->modalSubmitActionLabel(__('resources/locations.bulk_hours_submit'))
            ->deselectRecordsAfterCompletion()
            ->schema([
                Section::make(__('resources/locations.bulk_hours_regular'))
                    ->description(__('resources/locations.bulk_hours_regular_desc'))
                    ->schema([
                        Toggle::make('apply_regular')
                            ->label(__('resources/locations.bulk_hours_apply'))
                            ->live(),

                        Repeater::make('opening_hours')
                            ->hiddenLabel()
                            ->addActionLabel(__('resources/locations.bulk_hours_add_row'))
                            ->reorderable(false)
                            ->visible(fn (Get $get): bool => (bool) $get('apply_regular'))
                            ->default(collect(BusinessProfile::DAYS)->take(5)->map(fn (string $day): array => [
                                'day' => $day, 'open' => '09:00', 'close' => '18:00',
                            ])->values()->all())
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('day')
                                        ->label(__('pages/business_profile.field_day'))
                                        ->options(collect(BusinessProfile::DAYS)->mapWithKeys(
                                            fn (string $d): array => [$d => __('pages/business_profile.day_'.strtolower($d))],
                                        )->all())
                                        ->required(),
                                    TimePicker::make('open')
                                        ->label(__('pages/business_profile.field_open'))
                                        ->seconds(false)
                                        ->required(),
                                    TimePicker::make('close')
                                        ->label(__('pages/business_profile.field_close'))
                                        ->seconds(false)
                                        ->required(),
                                ]),
                            ]),
                    ]),

                Section::make(__('resources/locations.bulk_hours_special'))
                    ->description(__('resources/locations.bulk_hours_special_desc'))
                    ->schema([
                        Toggle::make('apply_special')
                            ->label(__('resources/locations.bulk_hours_apply'))
                            ->live(),

                        Select::make('holiday_events')
                            ->label(__('resources/locations.bulk_hours_holidays'))
                            ->helperText(__('resources/locations.bulk_hours_holidays_help'))
                            ->multiple()
                            ->options(fn (): array => self::upcomingCalendarEvents())
                            ->visible(fn (Get $get): bool => (bool) $get('apply_special') && self::upcomingCalendarEvents() !== []),

                        Repeater::make('special_hours')
                            ->hiddenLabel()
                            ->addActionLabel(__('pages/business_profile.add_special'))
                            ->reorderable(false)
                            ->default([])
                            ->visible(fn (Get $get): bool => (bool) $get('apply_special'))
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('start_date')
                                        ->label(__('pages/business_profile.field_start_date'))
                                        ->native(false)
                                        ->required(),
                                    DatePicker::make('end_date')
                                        ->label(__('pages/business_profile.field_end_date'))
                                        ->native(false)
                                        ->required(),
                                ]),
                                Toggle::make('closed')
                                    ->label(__('pages/business_profile.field_closed'))
                                    ->default(true)
                                    ->live(),
                                Grid::make(2)->schema([
                                    TimePicker::make('open')
                                        ->label(__('pages/business_profile.field_open'))
                                        ->seconds(false)
                                        ->required(fn (Get $get): bool => ! $get('closed')),
                                    TimePicker::make('close')
                                        ->label(__('pages/business_profile.field_close'))
                                        ->seconds(false)
                                        ->required(fn (Get $get): bool => ! $get('closed')),
                                ])->visible(fn (Get $get): bool => ! $get('closed')),
                            ]),
                    ]),
            ])
            ->action(fn (Collection $records, array $data) => self::applyBulkHours($records, $data));
    }

    /**
     * Upcoming external-calendar events (next 6 months) as date-labelled
     * options, so holiday closures don't have to be typed by hand.
     *
     * @return array<int, string>
     */
    protected static function upcomingCalendarEvents(): array
    {
        return once(fn (): array => ExternalCalendarEvent::query()
            ->whereBetween('date', [now()->toDateString(), now()->addMonths(6)->toDateString()])
            ->orderBy('date')
            ->limit(60)
            ->get()
            ->mapWithKeys(fn (ExternalCalendarEvent $event): array => [
                $event->id => $event->date->translatedFormat('D, M j').' · '.$event->title,
            ])
            ->all());
    }

    /**
     * @param  Collection<int, Location>  $records
     * @param  array<string, mixed>  $data
     */
    protected static function applyBulkHours(Collection $records, array $data): void
    {
        $openingHours = ($data['apply_regular'] ?? false) ? array_values((array) ($data['opening_hours'] ?? [])) : null;
        $specialHours = ($data['apply_special'] ?? false) ? array_values((array) ($data['special_hours'] ?? [])) : null;

        // Selected holiday-calendar events become closed days.
        if ($specialHours !== null && filled($data['holiday_events'] ?? null)) {
            $holidays = ExternalCalendarEvent::query()->whereIn('id', (array) $data['holiday_events'])->get();

            foreach ($holidays as $holiday) {
                $specialHours[] = [
                    'start_date' => $holiday->date->toDateString(),
                    'end_date' => $holiday->date->toDateString(),
                    'closed' => true,
                ];
            }
        }

        if (($openingHours === null || $openingHours === []) && ($specialHours === null || $specialHours === [])) {
            Notification::make()->title(__('resources/locations.bulk_hours_nothing'))->warning()->send();

            return;
        }

        $updater = app(ListingUpdater::class);
        $updated = 0;
        $failed = [];

        foreach ($records as $location) {
            if (blank($location->zernio_account_id) || blank($location->external_id)) {
                $failed[] = $location->name.' ('.__('resources/locations.bulk_hours_unmatched').')';

                continue;
            }

            try {
                $updater->pushHours($location, $openingHours, $specialHours);
                $updated++;
            } catch (Throwable $e) {
                $failed[] = $location->name.' ('.$e->getMessage().')';
            }
        }

        if ($updated > 0) {
            ActivityLogger::log('listing.bulk_hours', [
                'locations' => $updated,
                'regular' => $openingHours !== null,
                'special' => $specialHours !== null,
            ]);
        }

        if ($failed === []) {
            Notification::make()
                ->title(trans_choice('resources/locations.bulk_hours_done', $updated, ['count' => $updated]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(trans_choice('resources/locations.bulk_hours_partial', $updated, ['count' => $updated]))
                ->body(implode("\n", $failed))
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
