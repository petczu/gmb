<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Listings\ListingUpdater;
use App\Services\Zernio\ZernioRestClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

/**
 * Edits the Google Business Profile basics of a location (description, phone,
 * website, opening hours, special hours) via Zernio's gmb-location-details
 * GET/PATCH. The form is prefilled with LIVE values from Google, falling back
 * to the last locally saved copy (locations.listing_data) when offline.
 */
class BusinessProfile extends Page implements HasForms
{
    use InteractsWithForms;

    public const DAYS = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'business-profile';

    protected string $view = 'filament.app.pages.business-profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?int $locationId = null;

    /** @var ?array{verified: bool} */
    public ?array $listingStatus = null;

    public static function getNavigationLabel(): string
    {
        return __('pages/business_profile.nav');
    }

    public function getTitle(): string
    {
        return __('pages/business_profile.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Reached from Locations → "Edit info" row action, not from the menu.
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('edit_business_info') ?? false;
    }

    public function isConfigured(): bool
    {
        return app(ZernioRestClient::class)->configured();
    }

    public function mount(): void
    {
        // Preselect via ?location= (the Locations table "Edit info" action).
        $requested = (int) request()->query('location');

        $this->locationId = ($requested > 0 && Location::query()->whereKey($requested)->exists())
            ? $requested
            : ((int) (Location::query()->orderBy('name')->value('id') ?? 0) ?: null);

        $this->fillFromLocation();
    }

    /** True while the live Google values are still being fetched. */
    public bool $liveLoading = false;

    public function updatedLocationId(): void
    {
        $this->fillFromLocation();

        // Fetch the live values in a follow-up request so the switch is instant.
        if ($this->liveLoading) {
            $this->js('$wire.loadLiveDetails()');
        }
    }

    /**
     * Instant fill from the locally stored copy. The slow Google round-trip
     * happens afterwards in loadLiveDetails() (wire:init), so the page renders
     * immediately with a loading banner instead of blocking.
     */
    protected function fillFromLocation(): void
    {
        $location = $this->location();
        $this->listingStatus = null;
        $this->liveLoading = false;

        if ($location === null) {
            $this->form->fill([]);

            return;
        }

        $stored = $location->listing_data ?? [];

        $this->form->fill([
            'description' => $stored['description'] ?? null,
            'phone' => $location->phone,
            'additional_phones' => $stored['additional_phones'] ?? [],
            'website' => $location->website_url,
            'opening_hours' => $stored['opening_hours'] ?? [],
            'special_hours' => $stored['special_hours'] ?? [],
            'socials' => array_fill_keys(array_keys(ListingUpdater::SOCIAL_ATTRIBUTES), null),
        ]);

        $this->liveLoading = $this->isConfigured() && filled($location->zernio_account_id);
    }

    /** Deferred: pull the current values from Google and overlay them. */
    public function loadLiveDetails(): void
    {
        $location = $this->location();

        if ($location === null || ! $this->liveLoading) {
            $this->liveLoading = false;

            return;
        }

        $live = $this->fetchLiveDetails($location);
        $stored = $location->listing_data ?? [];

        $this->form->fill([
            'description' => $live['description'] ?? $stored['description'] ?? null,
            'phone' => $live['phone'] ?? $location->phone,
            'additional_phones' => $live['additional_phones'] ?? $stored['additional_phones'] ?? [],
            'website' => $live['website'] ?? $location->website_url,
            'opening_hours' => $live['opening_hours'] ?? $stored['opening_hours'] ?? [],
            'special_hours' => $live['special_hours'] ?? $stored['special_hours'] ?? [],
            'socials' => $this->fetchSocialUrls($location),
        ]);

        $this->liveLoading = false;
    }

    /**
     * Current social-profile URLs from Google's url_* attributes, keyed by the
     * ListingUpdater::SOCIAL_ATTRIBUTES suffix. All null on any failure
     * (offline-safe).
     *
     * @return array<string, ?string>
     */
    protected function fetchSocialUrls(Location $location): array
    {
        $urls = array_fill_keys(array_keys(ListingUpdater::SOCIAL_ATTRIBUTES), null);

        if (! $this->isConfigured() || blank($location->zernio_account_id) || blank($location->external_id)) {
            return $urls;
        }

        try {
            $attributes = app(ZernioRestClient::class)
                ->attributes((string) $location->zernio_account_id, (string) $location->external_id);
        } catch (Throwable) {
            return $urls;
        }

        foreach ($attributes as $attribute) {
            $key = str_replace('attributes/', '', (string) ($attribute['name'] ?? ''));

            if (array_key_exists($key, $urls)) {
                $urls[$key] = $attribute['values'][0] ?? null;
            }
        }

        return $urls;
    }

    /**
     * Current profile values from Google, mapped to the form shape. Also sets
     * the verification badge. Returns null on any failure (offline-safe).
     *
     * @return array<string, mixed>|null
     */
    protected function fetchLiveDetails(Location $location): ?array
    {
        if (! $this->isConfigured() || blank($location->zernio_account_id)) {
            return null;
        }

        try {
            $details = app(ZernioRestClient::class)->locationDetails(
                (string) $location->zernio_account_id,
                (string) $location->external_id,
            );
        } catch (Throwable) {
            return null; // status stays unknown; editing is still possible
        }

        $this->listingStatus = [
            'verified' => (bool) ($details['location']['isVerified'] ?? true),
        ];

        $openingHours = collect($details['regularHours']['periods'] ?? [])
            ->map(fn (array $period): array => [
                'day' => $period['openDay'] ?? null,
                'open' => $this->timeString($period['openTime'] ?? null),
                'close' => $this->timeString($period['closeTime'] ?? null),
            ])
            ->filter(fn (array $row): bool => filled($row['day']))
            ->values()
            ->all();

        $specialHours = collect($details['specialHours']['specialHourPeriods'] ?? [])
            ->map(fn (array $period): array => [
                'start_date' => $this->dateString($period['startDate'] ?? null),
                'end_date' => $this->dateString($period['endDate'] ?? $period['startDate'] ?? null),
                'closed' => (bool) ($period['closed'] ?? false),
                'open' => $this->timeString($period['openTime'] ?? null),
                'close' => $this->timeString($period['closeTime'] ?? null),
            ])
            ->filter(fn (array $row): bool => filled($row['start_date']))
            ->values()
            ->all();

        return [
            'description' => $details['profile']['description'] ?? null,
            'phone' => $details['phoneNumbers']['primaryPhone'] ?? null,
            'additional_phones' => array_values((array) ($details['phoneNumbers']['additionalPhones'] ?? [])),
            'website' => $details['websiteUri'] ?? null,
            'opening_hours' => $openingHours,
            'special_hours' => $specialHours,
        ];
    }

    /**
     * The API answers with either "HH:MM" strings or Google's raw
     * { hours, minutes } objects — normalize both for the TimePicker.
     */
    protected function timeString(mixed $time): ?string
    {
        if (is_array($time)) {
            return sprintf('%02d:%02d', (int) ($time['hours'] ?? 0), (int) ($time['minutes'] ?? 0));
        }

        return is_string($time) && $time !== '' ? substr($time, 0, 5) : null;
    }

    /** Google's { year, month, day } → "Y-m-d" for the DatePicker. */
    protected function dateString(mixed $date): ?string
    {
        if (! is_array($date) || ! isset($date['year'], $date['month'], $date['day'])) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', (int) $date['year'], (int) $date['month'], (int) $date['day']);
    }

    protected function location(): ?Location
    {
        return $this->locationId !== null ? Location::find($this->locationId) : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('pages/business_profile.section_basics'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('pages/business_profile.field_description'))
                            ->rows(5)
                            ->maxLength(750)
                            ->helperText(__('pages/business_profile.field_description_helper')),
                        Grid::make(2)->schema([
                            TextInput::make('phone')
                                ->label(__('pages/business_profile.field_phone'))
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('website')
                                ->label(__('pages/business_profile.field_website'))
                                ->url()
                                ->maxLength(2048),
                        ]),
                        TagsInput::make('additional_phones')
                            ->label(__('pages/business_profile.field_additional_phones'))
                            ->placeholder(__('pages/business_profile.field_additional_phones_placeholder'))
                            ->helperText(__('pages/business_profile.field_additional_phones_help')),
                    ]),

                Section::make(__('pages/business_profile.section_socials'))
                    ->description(__('pages/business_profile.section_socials_desc'))
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema(
                            collect(ListingUpdater::SOCIAL_ATTRIBUTES)->map(
                                fn (string $label, string $key): TextInput => TextInput::make('socials.'.$key)
                                    ->label($label)
                                    ->placeholder('https://…')
                                    ->maxLength(2048),
                            )->values()->all(),
                        ),
                    ]),

                Section::make(__('pages/business_profile.section_hours'))
                    ->description(__('pages/business_profile.section_hours_desc'))
                    ->schema([
                        Repeater::make('opening_hours')
                            ->hiddenLabel()
                            ->addActionLabel(__('pages/business_profile.add_hours'))
                            ->reorderable(false)
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('day')
                                        ->label(__('pages/business_profile.field_day'))
                                        ->options(collect(self::DAYS)->mapWithKeys(
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
                            ])
                            ->default([]),
                    ]),

                Section::make(__('pages/business_profile.section_special'))
                    ->description(__('pages/business_profile.section_special_desc'))
                    ->schema([
                        Repeater::make('special_hours')
                            ->hiddenLabel()
                            ->addActionLabel(__('pages/business_profile.add_special'))
                            ->reorderable(false)
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
                            ])
                            ->default([]),
                    ]),
            ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('pages/business_profile.save'))
                ->visible(fn (): bool => $this->isConfigured() && $this->locationId !== null)
                ->disabled(fn (): bool => $this->liveLoading)
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $location = $this->location();

        if ($location === null) {
            return;
        }

        if (blank($location->zernio_account_id) || blank($location->external_id)) {
            Notification::make()->title(__('pages/business_profile.unmatched'))->danger()->send();

            return;
        }

        try {
            app(ListingUpdater::class)->push($location, $this->form->getState());
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('pages/business_profile.save_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        ActivityLogger::log('listing.updated', ['location' => $location->name], $location);

        Notification::make()->title(__('pages/business_profile.saved'))->success()->send();
    }
}
