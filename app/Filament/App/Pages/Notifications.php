<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Notifications\ChatChannels;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationRecipients;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
        $routes = app(NotificationRecipients::class)->routes($this->workspace());

        $recipients = app(NotificationRecipients::class);
        $state = [];
        foreach (NotificationCategory::all() as $category) {
            $selection = $recipients->normalizeSelection($routes[$category] ?? []);
            $state['route_'.$category] = $selection['include'];
            $state['exclude_'.$category] = $selection['exclude'];
        }

        $chat = ChatChannels::config($this->workspace());
        $state['slack_enabled'] = $chat['slack_enabled'];
        $state['slack_webhook_url'] = $chat['slack_webhook_url'];
        $state['telegram_enabled'] = $chat['telegram_enabled'];
        $state['telegram_bot_token'] = $chat['telegram_bot_token'];
        $state['telegram_chat_id'] = $chat['telegram_chat_id'];
        $state['chat_language'] = $chat['language'];
        $state['chat_categories'] = $chat['categories'];

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $options = $this->recipientOptions();

        $chatEnabled = fn (Get $get): bool => (bool) $get('slack_enabled') || (bool) $get('telegram_enabled');

        // 1) Delivery channels — WHERE notifications go. One block per
        //    channel, each with its own switch, independent of the routing.
        $sections = [
            Section::make(__('pages/notifications.channel_email'))
                ->compact()
                ->schema([
                    Placeholder::make('channel_email_note')
                        ->hiddenLabel()
                        ->content(new HtmlString(
                            '<div style="display:flex;align-items:center;gap:.6rem;">'
                            .'<span style="padding:.25rem .6rem;border-radius:9999px;background:#dcfce7;color:#166534;font-weight:600;font-size:.8rem;">'.e(__('pages/notifications.channel_on')).'</span>'
                            .'<span style="font-size:.85rem;color:#6b7280;">'.e(__('pages/notifications.email_always_on')).'</span>'
                            .'</div>'
                        )),
                ]),

            Section::make('Slack')
                ->compact()
                ->schema([
                    Toggle::make('slack_enabled')
                        ->label(__('pages/notifications.channel_enable'))
                        ->live(),
                    TextInput::make('slack_webhook_url')
                        ->label(__('pages/notifications.slack_webhook'))
                        ->url()
                        ->placeholder('https://hooks.slack.com/services/…')
                        ->helperText(__('pages/notifications.slack_help'))
                        ->visible(fn (Get $get): bool => (bool) $get('slack_enabled')),
                ]),

            Section::make('Telegram')
                ->compact()
                ->schema([
                    Toggle::make('telegram_enabled')
                        ->label(__('pages/notifications.channel_enable'))
                        ->live(),
                    Grid::make(2)->schema([
                        TextInput::make('telegram_bot_token')
                            ->label(__('pages/notifications.telegram_token'))
                            ->password()
                            ->revealable()
                            ->helperText(__('pages/notifications.telegram_token_help')),
                        TextInput::make('telegram_chat_id')
                            ->label(__('pages/notifications.telegram_chat'))
                            ->helperText(__('pages/notifications.telegram_chat_help')),
                    ])->visible(fn (Get $get): bool => (bool) $get('telegram_enabled')),
                ]),

            // Shared settings for the chat channels above.
            Section::make(__('pages/notifications.chat_title'))
                ->compact()
                ->visible($chatEnabled)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('chat_language')
                            ->label(__('pages/notifications.chat_language'))
                            ->options(Locales::options())
                            ->selectablePlaceholder(false),
                        Select::make('chat_categories')
                            ->label(__('pages/notifications.chat_categories'))
                            ->multiple()
                            ->options(collect(NotificationCategory::all())->mapWithKeys(
                                fn (string $c): array => [$c => __('pages/notifications.cat_'.$c)],
                            )->all()),
                    ]),
                ]),
        ];

        // 2) Email routing — WHO receives which category.
        foreach (NotificationCategory::all() as $category) {
            $sections[] = Section::make(__('pages/notifications.cat_'.$category))
                ->description(__('pages/notifications.cat_'.$category.'_desc'))
                ->schema([
                    Select::make('route_'.$category)
                        ->label(__('pages/notifications.included'))
                        ->multiple()
                        ->options($options)
                        ->placeholder(__('pages/notifications.default_owner'))
                        ->helperText(__('pages/notifications.recipients_help')),

                    // Subtracted from the included set: e.g. include a whole
                    // role, exclude one person. Ignored when Included is empty
                    // (that falls back to the workspace owner).
                    Select::make('exclude_'.$category)
                        ->label(__('pages/notifications.excluded'))
                        ->multiple()
                        ->options($options)
                        ->placeholder(__('pages/notifications.excluded_none'))
                        ->helperText(__('pages/notifications.excluded_help')),
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
            $groups[NotificationRecipients::ROLE_PREFIX.$role] = Lang::has($key)
                ? __($key)
                : __('pages/notifications.group_role', ['role' => Str::headline($role)]);
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
        $defined = Role::query()
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
            Action::make('testChat')
                ->label(__('pages/notifications.chat_test'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->visible(fn (): bool => ChatChannels::enabled($this->workspace()))
                ->action(function (): void {
                    ChatChannels::sendTest($this->workspace());
                    Notification::make()->title(__('pages/notifications.chat_test_sent'))->success()->send();
                }),

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
            $include = array_values($state['route_'.$category] ?? []);
            $exclude = array_values($state['exclude_'.$category] ?? []);
            if ($include === []) {
                // No included = fall back to the owner; exclude is meaningless.
                continue;
            }

            // Keep the legacy flat shape when nothing is excluded; only switch
            // to {include, exclude} when there is an exclusion.
            $routes[$category] = $exclude === []
                ? $include
                : ['include' => $include, 'exclude' => $exclude];
        }

        $w = $this->workspace();
        $w->setAttribute(NotificationRecipients::ROUTES_KEY, $routes);
        $w->setAttribute('chat_channels', [
            'slack_enabled' => (bool) ($state['slack_enabled'] ?? false),
            'slack_webhook_url' => $state['slack_webhook_url'] ?? null,
            'telegram_enabled' => (bool) ($state['telegram_enabled'] ?? false),
            'telegram_bot_token' => $state['telegram_bot_token'] ?? null,
            'telegram_chat_id' => $state['telegram_chat_id'] ?? null,
            'language' => $state['chat_language'] ?? 'en',
            'categories' => array_values($state['chat_categories'] ?? []),
        ]);
        $w->save();

        Notification::make()->title(__('pages/notifications.saved'))->success()->send();
    }
}
