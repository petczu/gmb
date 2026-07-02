<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\User;
use App\Models\Workspace;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationRecipients;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Notifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 82;

    protected static ?string $slug = 'notifications';

    protected string $view = 'filament.app.pages.notifications';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.notifications');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_team') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_team') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function mount(): void
    {
        $routes = app(NotificationRecipients::class)->routes($this->workspace());

        $state = [];
        foreach (NotificationCategory::all() as $category) {
            $state['route_'.$category] = $routes[$category] ?? [];
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $options = $this->recipientOptions();

        $sections = [
            Section::make(__('pages/notifications.channels'))
                ->description(__('pages/notifications.channels_desc'))
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('channels_note')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div style="display:flex;gap:.5rem;flex-wrap:wrap;font-size:.8rem;">'
                            .'<span style="padding:.25rem .6rem;border-radius:9999px;background:#dcfce7;color:#166534;font-weight:600;">'.e(__('pages/notifications.channel_email')).'</span>'
                            .'<span style="padding:.25rem .6rem;border-radius:9999px;background:#f3f4f6;color:#6b7280;">'.e(__('pages/notifications.channel_sms')).'</span>'
                            .'<span style="padding:.25rem .6rem;border-radius:9999px;background:#f3f4f6;color:#6b7280;">'.e(__('pages/notifications.channel_whatsapp')).'</span>'
                            .'</div>'
                        )),
                ]),
        ];
        foreach (NotificationCategory::all() as $category) {
            $sections[] = Section::make(__('pages/notifications.cat_'.$category))
                ->description(__('pages/notifications.cat_'.$category.'_desc'))
                ->schema([
                    Select::make('route_'.$category)
                        ->label(__('pages/notifications.recipients'))
                        ->multiple()
                        ->options($options)
                        ->placeholder(__('pages/notifications.default_owner'))
                        ->helperText(__('pages/notifications.recipients_help')),
                ]);
        }

        return $schema->statePath('data')->components($sections);
    }

    /**
     * Grouped recipient options: role-based groups ("All owners", "Everyone")
     * on top, then individual members (including Guests).
     *
     * @return array<string, array<int|string, string>>
     */
    protected function recipientOptions(): array
    {
        $members = $this->workspace()->users()->get();

        $groups = [NotificationRecipients::EVERYONE => __('pages/notifications.everyone')];
        foreach ($this->roleNames() as $role) {
            $key = 'pages/notifications.group_'.$role;
            $groups[NotificationRecipients::ROLE_PREFIX.$role] = \Illuminate\Support\Facades\Lang::has($key)
                ? __($key)
                : __('pages/notifications.group_role', ['role' => \Illuminate\Support\Str::headline($role)]);
        }

        $people = $members->mapWithKeys(function (User $user): array {
            $label = $user->name.' · '.$user->email;
            if (($user->pivot->membership_type ?? null) === 'guest') {
                $label .= ' ('.__('pages/notifications.guest').')';
            }

            return [$user->id => $label];
        })->all();

        return [
            __('pages/notifications.groups') => $groups,
            __('pages/notifications.people') => $people,
        ];
    }

    /**
     * Every role defined for this workspace (so groups can be pre-configured
     * before anyone holds the role), standard roles first then custom ones.
     *
     * @return list<string>
     */
    protected function roleNames(): array
    {
        $defined = \App\Models\Role::query()
            ->where('team_id', $this->workspace()->id)
            ->pluck('name')
            ->all();

        return array_values(array_unique(array_merge(
            array_values(array_intersect(['owner', 'admin', 'member', 'guest'], $defined)),
            $defined,
        )));
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

        $routes = [];
        foreach (NotificationCategory::all() as $category) {
            // Values are a mix of user ids and group tokens (everyone / role:*);
            // store them verbatim — the resolver expands groups at send time.
            $selected = array_values($state['route_'.$category] ?? []);
            if ($selected !== []) {
                $routes[$category] = $selected;
            }
        }

        $w = $this->workspace();
        $w->setAttribute(NotificationRecipients::ROUTES_KEY, $routes);
        $w->save();

        Notification::make()->title(__('pages/notifications.saved'))->success()->send();
    }
}
