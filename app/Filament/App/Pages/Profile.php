<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.app.pages.profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.profile');
    }

    // Reached from the top-right user menu, not the sidebar.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $u = auth()->user();

        $this->form->fill([
            'avatar_path' => $u->avatar_path,
            'name' => $u->name,
            'email' => $u->email,
            'timezone' => $u->timezone ?? config('app.timezone'),
            'week_start' => $u->week_start ?? 'monday',
            'locale' => $u->locale ?? 'en',
            'product_emails' => (bool) $u->getAttribute('product_emails'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('pages/profile.profile_section'))
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label(__('pages/profile.photo'))
                            ->avatar()
                            ->image()
                            ->disk('uploads')
                            ->directory('avatars'),
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('email')->email()->required()->maxLength(160),
                    ])->columns(2),

                Section::make(__('pages/profile.password_section'))
                    ->description(__('pages/profile.password_section_desc'))
                    ->schema([
                        TextInput::make('password')
                            ->password()->revealable()->confirmed()->maxLength(255)
                            ->autocomplete('new-password')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label(__('pages/profile.confirm_password'))->password()->revealable()->maxLength(255)
                            ->autocomplete('new-password')->dehydrated(false),
                    ])->columns(2),

                Section::make(__('pages/profile.preferences_section'))
                    ->schema([
                        Select::make('locale')
                            ->label(__('pages/profile.interface_language'))
                            ->options(Locales::options())
                            ->default('en')
                            ->selectablePlaceholder(false),
                        Select::make('timezone')
                            ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                            ->searchable()
                            ->default(config('app.timezone')),
                        Select::make('week_start')
                            ->label(__('pages/profile.first_day_of_week'))
                            ->options(['monday' => __('pages/profile.monday'), 'sunday' => __('pages/profile.sunday')])
                            ->default('monday')
                            ->selectablePlaceholder(false),
                        Toggle::make('product_emails')
                            ->label(__('pages/profile.product_emails'))
                            ->helperText(__('pages/profile.product_emails_help'))
                            ->inline(false),
                    ])->columns(2),

                Section::make(__('pages/profile.two_factor_section'))
                    ->description(__('pages/profile.two_factor_desc'))
                    ->visible(fn (): bool => Filament::hasMultiFactorAuthentication())
                    ->extraAttributes(['class' => 'mfa-form'])
                    ->schema(
                        collect(Filament::getMultiFactorAuthenticationProviders())
                            ->map(function (MultiFactorAuthenticationProvider $provider): Group {
                                $components = array_map(function ($component) use ($provider) {
                                    if ($component instanceof Actions) {
                                        // Render the action links as real buttons, aligned to the right,
                                        // and rename "Regenerate recovery codes" → "Get codes".
                                        $component
                                            ->actions($this->styleMfaActions($provider))
                                            ->alignment(Alignment::End);
                                    }

                                    return $component;
                                }, $provider->getManagementSchemaComponents());

                                return Group::make($components)->statePath($provider->getId());
                            })
                            ->all()
                    ),
            ]);
    }

    /**
     * The MFA providers expose their Set up / Disable / Regenerate actions via getActions().
     * Re-style them as buttons and give the recovery-codes action a shorter label.
     *
     * @return array<int, Action>
     */
    protected function styleMfaActions(MultiFactorAuthenticationProvider $provider): array
    {
        if (! method_exists($provider, 'getActions')) {
            return [];
        }

        return array_map(function (Action $action): Action {
            $action->button();

            if ($action->getName() === 'regenerateAppAuthenticationRecoveryCodes') {
                $action->label(__('pages/profile.mfa_get_codes'));
            }

            return $action;
        }, $provider->getActions());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label(__('common.save'))->icon(Heroicon::OutlinedCheck)->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $u = auth()->user();

        $u->fill([
            'name' => $state['name'],
            'email' => $state['email'],
            'avatar_path' => $state['avatar_path'] ?? null,
            'timezone' => $state['timezone'] ?? null,
            'week_start' => $state['week_start'] ?? 'monday',
            'locale' => $state['locale'] ?? 'en',
        ]);

        $u->forceFill(['product_emails' => (bool) ($state['product_emails'] ?? true)]);

        if (filled($state['password'] ?? null)) {
            $u->password = Hash::make($state['password']);
        }

        $u->save();

        Notification::make()->title(__('pages/profile.profile_saved'))->success()->send();
    }
}
