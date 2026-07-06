<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Alerts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'alerts';

    protected string $view = 'filament.app.pages.alerts';

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** The alert toggles, each defaulting to ON until turned off. */
    private const TOGGLES = [
        'notify_goal_progress',
        'notify_coaching',
        'notify_stalled',
        'notify_negative_streak',
        'notify_spike',
        'notify_rating_drop',
    ];

    public static function getNavigationLabel(): string
    {
        return __('nav.alerts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_notifications') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_notifications') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function mount(): void
    {
        $w = $this->workspace();

        $state = [];
        foreach (self::TOGGLES as $toggle) {
            $value = $w->getAttribute($toggle);
            $state[$toggle] = $value === null ? true : (bool) $value;
        }

        foreach (Location::query()->get() as $location) {
            $state['goal_'.$location->id] = $location->review_goal;
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('pages/alerts.goals_section'))
                    ->description(__('pages/alerts.goals_section_desc'))
                    ->schema($this->goalFields())
                    ->columns(2),

                Section::make(__('pages/alerts.alerts_section'))
                    ->description(__('pages/alerts.alerts_section_desc'))
                    ->schema([
                        Toggle::make('notify_goal_progress')
                            ->label(__('pages/alerts.toggle_goal_progress'))
                            ->helperText(__('pages/alerts.toggle_goal_progress_help')),
                        Toggle::make('notify_coaching')
                            ->label(__('pages/alerts.toggle_coaching'))
                            ->helperText(__('pages/alerts.toggle_coaching_help')),
                        Toggle::make('notify_stalled')
                            ->label(__('pages/alerts.toggle_stalled'))
                            ->helperText(__('pages/alerts.toggle_stalled_help')),
                        Toggle::make('notify_negative_streak')
                            ->label(__('pages/alerts.toggle_negative_streak'))
                            ->helperText(__('pages/alerts.toggle_negative_streak_help')),
                        Toggle::make('notify_spike')
                            ->label(__('pages/alerts.toggle_spike'))
                            ->helperText(__('pages/alerts.toggle_spike_help')),
                        Toggle::make('notify_rating_drop')
                            ->label(__('pages/alerts.toggle_rating_drop'))
                            ->helperText(__('pages/alerts.toggle_rating_drop_help')),
                    ]),
            ]);
    }

    /**
     * One numeric "monthly goal" input per location. Locations are read-only
     * synced records, so the goal is the only editable field we surface here.
     *
     * @return list<Field|Component>
     */
    protected function goalFields(): array
    {
        $fields = [];

        foreach (Location::query()->orderBy('name')->get() as $location) {
            $fields[] = TextInput::make('goal_'.$location->id)
                ->label($location->name)
                ->numeric()
                ->minValue(0)
                ->maxValue(100000)
                ->suffix(__('pages/alerts.per_month'))
                ->placeholder(__('pages/alerts.no_goal'));
        }

        if ($fields === []) {
            return [
                Placeholder::make('no_locations')
                    ->hiddenLabel()
                    ->content(__('pages/alerts.no_locations')),
            ];
        }

        return $fields;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $w = $this->workspace();

        foreach (self::TOGGLES as $toggle) {
            $w->setAttribute($toggle, (bool) ($state[$toggle] ?? false));
        }
        $w->save();

        foreach (Location::query()->get() as $location) {
            $value = $state['goal_'.$location->id] ?? null;
            $location->review_goal = ($value === null || $value === '') ? null : (int) $value;
            $location->save();
        }

        Notification::make()->title(__('pages/alerts.saved'))->success()->send();
    }
}
