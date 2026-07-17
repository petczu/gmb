<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\Locations\LocationResource;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Listings\ListingUpdater;
use App\Services\Locations\LocationTransferService;
use App\Services\Zernio\ZernioRestClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
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
            'logo_path' => $location->logo_path,
            'description' => $stored['description'] ?? null,
            'phone' => $location->phone,
            'additional_phones' => $stored['additional_phones'] ?? [],
            'website' => $location->website_url,
            'timezone' => $location->timezone,
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
            'logo_path' => $location->logo_path,
            'description' => $live['description'] ?? $stored['description'] ?? null,
            'phone' => $live['phone'] ?? $location->phone,
            'additional_phones' => $live['additional_phones'] ?? $stored['additional_phones'] ?? [],
            'website' => $live['website'] ?? $location->website_url,
            'timezone' => $location->timezone,
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
                        // Our own field (not pushed to Google): shown on the post
                        // preview card, falling back to the workspace logo.
                        FileUpload::make('logo_path')
                            ->label(__('pages/business_profile.field_logo'))
                            ->image()
                            ->disk('uploads')
                            ->directory('logos')
                            ->helperText(__('pages/business_profile.field_logo_helper')),
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
                        Select::make('timezone')
                            ->label(__('pages/business_profile.field_timezone'))
                            ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn (string $tz): array => [$tz => $tz])->all())
                            ->searchable()
                            ->native(false)
                            ->helperText(__('pages/business_profile.field_timezone_helper')),
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

            // Move this location, with everything tied exclusively to it, into
            // another workspace. It lands disconnected and must be reconnected
            // to Google there.
            Action::make('moveWorkspace')
                ->label(__('resources/locations.move'))
                ->icon(Heroicon::OutlinedArrowRightCircle)
                ->color('gray')
                ->visible(fn (): bool => $this->locationId !== null
                    && (auth()->user()?->can('edit_business_info') ?? false)
                    && $this->otherWorkspaces()->isNotEmpty())
                ->modalHeading(__('resources/locations.move_heading'))
                ->modalSubmitActionLabel(__('resources/locations.move_submit'))
                ->schema([
                    Placeholder::make('move_preview')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => $this->movePreviewHtml()),
                    Select::make('target')
                        ->label(__('resources/locations.move_target'))
                        ->options(fn (): array => $this->otherWorkspaces()->pluck('name', 'id')->all())
                        ->required()
                        ->native(false),
                ])
                ->action(fn (array $data) => $this->moveLocation($data)),
        ];
    }

    /** The current user's other workspaces (valid move targets). */
    private function otherWorkspaces(): Collection
    {
        $currentId = session('current_workspace_id');

        return rescue(fn (): Collection => (auth()->user()?->workspaces ?? collect())
            ->reject(fn (Workspace $w): bool => $w->id === $currentId)
            ->values(), collect(), report: false);
    }

    /** A "what will move" summary for the confirmation modal. */
    private function movePreviewHtml(): HtmlString
    {
        $location = $this->location();
        if ($location === null) {
            return new HtmlString('');
        }

        $preview = app(LocationTransferService::class)->preview((int) $location->id, Workspace::find(session('current_workspace_id')));

        $rows = [
            'reviews' => __('resources/locations.move_reviews'),
            'posts' => __('resources/locations.move_posts'),
            'automations' => __('resources/locations.move_automations'),
            'report_schedules' => __('resources/locations.move_reports'),
            'auto_reply_rules' => __('resources/locations.move_rules'),
            'agents' => __('resources/locations.move_agents'),
        ];

        $items = '';
        foreach ($rows as $key => $label) {
            $items .= '<li style="display:flex; justify-content:space-between; gap:1rem;"><span>'.e($label).'</span><b>'.(int) ($preview[$key] ?? 0).'</b></li>';
        }

        // Overlay shown while the move runs (it's a heavy cross-database copy):
        // a spinner over the modal so it doesn't look frozen. wire:target scopes
        // it to the action call, so picking the target workspace doesn't flash it.
        $loader = '<div wire:loading wire:target="callMountedAction, callSchemaComponentMethod" '
            .'style="position:absolute; inset:0; z-index:20; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.9rem; background:rgba(255,255,255,.92); border-radius:.5rem;">'
            .'<svg width="42" height="42" viewBox="0 0 50 50" style="animation:mv-spin 1s linear infinite;"><circle cx="25" cy="25" r="20" fill="none" stroke="#2d19ec" stroke-width="5" stroke-linecap="round" stroke-dasharray="80 40"/></svg>'
            .'<span style="font-size:.9rem; font-weight:600; color:#374151;">'.e(__('resources/locations.move_running')).'</span>'
            .'<span style="font-size:.78rem; color:#6b7280;">'.e(__('resources/locations.move_running_hint')).'</span>'
            .'<style>@keyframes mv-spin{to{transform:rotate(360deg)}}</style>'
            .'</div>';

        return new HtmlString(
            '<div style="position:relative; font-size:.85rem; color:#374151;">'
            .'<p style="margin:0 0 .5rem;">'.e(__('resources/locations.move_intro')).'</p>'
            .'<ul style="list-style:none; margin:0 0 .6rem; padding:0; display:flex; flex-direction:column; gap:.25rem;">'.$items.'</ul>'
            .'<p style="margin:0; color:#b45309;">'.e(__('resources/locations.move_reconnect')).'</p>'
            .$loader
            .'</div>'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function moveLocation(array $data): void
    {
        $location = $this->location();
        $source = Workspace::find(session('current_workspace_id'));
        $target = $this->otherWorkspaces()->firstWhere('id', $data['target'] ?? null);

        if ($location === null || $source === null || $target === null) {
            Notification::make()->title(__('resources/locations.move_failed'))->danger()->send();

            return;
        }

        // A location with a lot of reviews/posts is a heavy cross-database copy;
        // don't let the request time out mid-move.
        set_time_limit(300);

        try {
            app(LocationTransferService::class)->transfer((int) $location->id, $source, $target);
        } catch (Throwable $e) {
            Notification::make()->title(__('resources/locations.move_failed'))->body($e->getMessage())->danger()->send();

            return;
        }

        ActivityLogger::log('location.moved', ['location' => $location->name, 'to' => $target->name]);
        Notification::make()
            ->title(__('resources/locations.move_done', ['workspace' => $target->name]))
            ->body(__('resources/locations.move_done_body'))
            ->success()
            ->persistent()
            ->send();

        // The location no longer lives here — leave the editor.
        $this->redirect(LocationResource::getUrl());
    }

    public function save(): void
    {
        $location = $this->location();

        if ($location === null) {
            return;
        }

        $state = $this->form->getState();

        // Timezone and logo are our own fields, not Google/Zernio properties —
        // persist them regardless of the listing push (and keep them out of the
        // push payload).
        $tz = $state['timezone'] ?? null;
        $location->forceFill([
            'timezone' => filled($tz) ? $tz : null,
            'logo_path' => filled($state['logo_path'] ?? null) ? $state['logo_path'] : null,
        ])->save();
        unset($state['timezone'], $state['logo_path']);

        if (blank($location->zernio_account_id) || blank($location->external_id)) {
            Notification::make()->title(__('pages/business_profile.unmatched'))->danger()->send();

            return;
        }

        try {
            app(ListingUpdater::class)->push($location, $state);
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
