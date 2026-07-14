<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Locations;

use App\Filament\App\Pages\BusinessProfile;
use App\Models\ExternalCalendarEvent;
use App\Models\Location;
use App\Models\ScheduledListingUpdate;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Listings\ListingUpdater;
use Carbon\CarbonImmutable;
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
use Illuminate\Support\Collection;
use Throwable;

/**
 * Shared form + apply logic for the "Edit hours" header action on Locations:
 * set regular and/or special hours on many locations at once, immediately or
 * scheduled for a future date (see ScheduledListingUpdate).
 */
class HoursBulkEdit
{
    /** @return array<int, mixed> */
    public static function schema(): array
    {
        return [
            Select::make('locations')
                ->label(__('resources/locations.bulk_hours_locations'))
                ->multiple()
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): array => Location::query()->pluck('id')->all())
                ->required(),

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
                                    ->prefixIcon(Heroicon::OutlinedCalendar)
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('end_date')
                                    ->label(__('pages/business_profile.field_end_date'))
                                    ->prefixIcon(Heroicon::OutlinedCalendar)
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

            DatePicker::make('apply_on')
                ->label(__('resources/locations.bulk_hours_apply_on'))
                ->helperText(__('resources/locations.bulk_hours_apply_on_help'))
                ->prefixIcon(Heroicon::OutlinedCalendar)
                ->native(false)
                ->minDate(now()->addDay()),
        ];
    }

    /**
     * Upcoming external-calendar events (next 6 months) as date-labelled
     * options, so holiday closures don't have to be typed by hand.
     *
     * @return array<int, string>
     */
    public static function upcomingCalendarEvents(): array
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
     * @param  array<string, mixed>  $data
     */
    public static function apply(array $data): void
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

        $locations = Location::query()->whereIn('id', array_map('intval', (array) ($data['locations'] ?? [])))->get();

        if ($locations->isEmpty()) {
            Notification::make()->title(__('resources/locations.bulk_hours_nothing'))->warning()->send();

            return;
        }

        // A future date parks the edit; listings:apply-scheduled pushes it
        // early on that day.
        $applyOn = filled($data['apply_on'] ?? null) ? CarbonImmutable::parse((string) $data['apply_on']) : null;

        if ($applyOn !== null && $applyOn->isAfter(today())) {
            ScheduledListingUpdate::create([
                'location_ids' => $locations->pluck('id')->all(),
                'opening_hours' => $openingHours,
                'special_hours' => $specialHours,
                'apply_on' => $applyOn->toDateString(),
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()?->name,
            ]);

            ActivityLogger::log('listing.hours_scheduled', [
                'locations' => $locations->count(),
                'apply_on' => $applyOn->toDateString(),
            ]);

            Notification::make()
                ->title(__('resources/locations.bulk_hours_scheduled', ['date' => $applyOn->translatedFormat('j M Y')]))
                ->body(trans_choice('resources/locations.bulk_hours_scheduled_body', $locations->count(), ['count' => $locations->count()]))
                ->success()
                ->send();

            return;
        }

        [$updated, $failed] = self::push($locations, $openingHours, $specialHours);

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

    /**
     * Push hours to each matched location; shared by the modal (apply now)
     * and the scheduled-updates command.
     *
     * @param  Collection<int, Location>  $locations
     * @param  ?array<int, array<string, mixed>>  $openingHours
     * @param  ?array<int, array<string, mixed>>  $specialHours
     * @return array{0: int, 1: list<string>} updated count + failure lines
     */
    public static function push(Collection $locations, ?array $openingHours, ?array $specialHours): array
    {
        $updater = app(ListingUpdater::class);
        $updated = 0;
        $failed = [];

        foreach ($locations as $location) {
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

        return [$updated, $failed];
    }
}
