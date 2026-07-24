<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Account\WorkspaceDeletionService;
use App\Services\Billing\LocationBilling;
use App\Support\BusinessCategories;
use App\Support\Countries;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Company extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 70;

    protected static ?string $slug = 'company';

    protected string $view = 'filament.app.pages.company';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.company');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_company') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_company') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function mount(): void
    {
        $w = $this->workspace();

        $this->form->fill([
            'name' => $w->name,
            'entity_type' => $w->entity_type ?? 'company',
            'legal_name' => $w->legal_name,
            'website' => $w->website,
            'contact_email' => $w->contact_email,
            'contact_phone' => $w->contact_phone,
            'business_category' => $w->business_category,
            'billing_country' => $w->billing_country ?? 'AT',
            'address_line1' => $w->address_line1,
            'address_line2' => $w->address_line2,
            'postal_code' => $w->postal_code,
            'city' => $w->city,
            'vat_number' => $w->vat_number,
            'brand_color' => $w->brand_color,
            'logo_path' => $w->logo_path,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $canBrand = app(LocationBilling::class)->allows($this->workspace(), Plans::WHITE_LABEL);

        return $schema
            ->statePath('data')
            ->components([
                ...($this->workspace()->isPendingDeletion() ? [
                    Section::make(__('pages/company.deletion_section'))
                        ->description(__('pages/company.deletion_section_desc'))
                        ->schema([
                            Placeholder::make('purge_at')
                                ->label(__('pages/company.permanent_deletion_on'))
                                ->content(fn (): string => $this->workspace()->deletionPurgeAt()?->translatedFormat('j. F Y') ?? '—'),
                        ]),
                ] : []),
                Section::make(__('pages/company.profile_section'))
                    ->description(__('pages/company.profile_section_desc'))
                    ->schema([
                        Radio::make('entity_type')
                            ->label(__('pages/company.entity_type'))
                            ->options([
                                'company' => __('pages/company.entity_company'),
                                'individual' => __('pages/company.entity_individual'),
                            ])
                            ->inline()
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('name')->label(__('pages/company.display_name'))->required()->maxLength(120),
                        TextInput::make('legal_name')->label(__('pages/company.legal_name'))->maxLength(160)
                            ->visible(fn (Get $get): bool => $get('entity_type') !== 'individual'),
                        // The https:// prefix is visual — people type bare domains
                        // ("google.com"), so the strict `url` rule would reject
                        // them. Strip any typed scheme, validate the full URL.
                        TextInput::make('website')
                            ->label(__('pages/company.website'))
                            ->prefix('https://')
                            ->maxLength(200)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? preg_replace('~^https?://~i', '', trim($state))
                                : null)
                            ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                $candidate = 'https://'.preg_replace('~^https?://~i', '', trim((string) $value));
                                $host = parse_url($candidate, PHP_URL_HOST);

                                if (! filter_var($candidate, FILTER_VALIDATE_URL) || ! is_string($host) || ! str_contains($host, '.')) {
                                    $fail(__('validation.url', ['attribute' => 'website']));
                                }
                            }),
                        TextInput::make('contact_email')->label(__('pages/company.contact_email'))->email()->maxLength(160),
                        TextInput::make('contact_phone')->label(__('pages/company.contact_phone'))->tel()->maxLength(60),
                        Select::make('business_category')
                            ->label(__('pages/company.business_category'))
                            ->options(BusinessCategories::options())
                            ->searchable()
                            ->placeholder(__('pages/company.business_category_placeholder'))
                            ->helperText(__('pages/company.business_category_helper')),
                    ])
                    ->columns(2),

                Section::make(__('pages/company.billing_section'))
                    ->description(__('pages/company.billing_section_desc'))
                    ->schema([
                        Select::make('billing_country')
                            ->label(__('pages/company.country'))
                            ->options(Countries::list())
                            ->searchable()
                            ->default('AT'),
                        TextInput::make('vat_number')->label(__('pages/company.vat_number'))->maxLength(40)
                            ->helperText(__('pages/company.vat_helper'))
                            ->visible(fn (Get $get): bool => $get('entity_type') !== 'individual'),
                        TextInput::make('address_line1')->label(__('pages/company.address_line1'))->maxLength(200),
                        TextInput::make('address_line2')->label(__('pages/company.address_line2'))->maxLength(200),
                        TextInput::make('postal_code')->label(__('pages/company.postal_code'))->maxLength(20),
                        TextInput::make('city')->label(__('pages/company.city'))->maxLength(120),
                    ])
                    ->columns(2),

                Section::make($canBrand ? __('pages/company.branding_section') : __('pages/company.branding_section_pro'))
                    ->description($canBrand
                        ? __('pages/company.branding_desc')
                        : __('pages/company.branding_desc_locked'))
                    ->schema([
                        // The company logo is available on every plan — it also
                        // identifies the workspace in the switcher. White-label on
                        // reports stays plan-gated (see report rendering).
                        FileUpload::make('logo_path')
                            ->label(__('pages/company.logo'))
                            ->image()
                            ->disk('uploads')
                            ->directory('logos')
                            ->imagePreviewHeight('60')
                            ->helperText(__('pages/company.logo_helper')),
                        ColorPicker::make('brand_color')
                            ->label(__('pages/company.brand_color'))
                            ->disabled(! $canBrand),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => ! $this->workspace()->isPendingDeletion())
                ->action('save'),

            Action::make('cancelDeletion')
                ->label(__('pages/company.cancel_deletion'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('success')
                ->visible(fn (): bool => $this->canManageDeletion() && $this->workspace()->isPendingDeletion())
                ->requiresConfirmation()
                ->modalHeading(__('pages/company.cancel_deletion_heading'))
                ->modalDescription(__('pages/company.cancel_deletion_desc'))
                ->modalSubmitActionLabel(__('pages/company.cancel_deletion_submit'))
                ->action(function (): void {
                    app(WorkspaceDeletionService::class)->cancelRequest($this->workspace());

                    Notification::make()->title(__('pages/company.deletion_cancelled'))->success()->send();
                    $this->redirect(static::getUrl());
                }),

            Action::make('deleteWorkspace')
                ->label(__('pages/company.delete_workspace'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn (): bool => $this->canManageDeletion() && ! $this->workspace()->isPendingDeletion())
                ->modalHeading(__('pages/company.delete_workspace_heading'))
                ->modalDescription(__('pages/company.delete_workspace_desc'))
                ->modalSubmitActionLabel(__('pages/company.delete_workspace_submit'))
                ->modalIconColor('danger')
                ->schema([
                    TextInput::make('confirm')
                        ->label(__('pages/company.confirm_name'))
                        ->placeholder(fn (): string => $this->workspace()->name)
                        ->required(),
                ])
                ->action(function (array $data, Action $action): void {
                    $workspace = $this->workspace();

                    if (trim((string) ($data['confirm'] ?? '')) !== $workspace->name) {
                        Notification::make()->title(__('pages/company.name_mismatch'))->danger()->send();
                        $action->halt();
                    }

                    app(WorkspaceDeletionService::class)->request($workspace, auth()->user());

                    Notification::make()
                        ->title(__('pages/company.deletion_scheduled'))
                        ->body(__('pages/company.deletion_scheduled_body'))
                        ->warning()
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    /** Only the workspace owner may request/cancel deletion. */
    protected function canManageDeletion(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $w = $this->workspace();

        // `name` is a real column; the rest live in the tenant `data` JSON.
        $w->name = $state['name'];
        foreach (['entity_type', 'legal_name', 'website', 'contact_email', 'contact_phone', 'business_category', 'billing_country', 'address_line1', 'address_line2', 'postal_code', 'city', 'vat_number', 'brand_color', 'logo_path'] as $key) {
            $w->{$key} = $state[$key] ?? null;
        }
        $w->save();

        Notification::make()->title(__('pages/company.settings_saved'))->success()->send();
    }
}
