<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Jobs\BuildReviewWidgetSnapshotJob;
use App\Models\Location;
use App\Models\ReviewWidget;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

/**
 * Builder for embeddable review-showcase widgets: settings form on the left,
 * LIVE preview on the right (review-pages pattern). Saving persists the config
 * to the CENTRAL row and rebuilds the tenancy-free snapshot the embed serves.
 */
class ReviewWidgets extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracketSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'review-widgets';

    protected string $view = 'filament.app.pages.review-widgets';

    /** @var array<string, mixed> */
    public ?array $data = [];

    #[Url(as: 'widget', history: true)]
    public ?int $widgetId = null;

    #[Url(history: true)]
    public bool $editing = false;

    public static function getNavigationLabel(): string
    {
        return __('pages/review_widgets.nav');
    }

    public function getTitle(): string
    {
        return __('pages/review_widgets.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_review_pages') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_review_pages') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function mount(): void
    {
        if ($this->widgetId !== null) {
            $widget = ReviewWidget::query()
                ->where('workspace_id', $this->workspace()->id)
                ->find($this->widgetId);

            if ($widget !== null) {
                $this->form->fill($this->stateFromWidget($widget));
                $this->editing = true;

                return;
            }

            $this->widgetId = null;
            $this->editing = false;
        }

        $this->form->fill($this->defaultState());
    }

    public function edit(int $id): void
    {
        $widget = ReviewWidget::query()->where('workspace_id', $this->workspace()->id)->findOrFail($id);

        $this->widgetId = $widget->id;
        $this->form->fill($this->stateFromWidget($widget));
        $this->editing = true;
    }

    public function newWidget(): void
    {
        $this->widgetId = null;
        $this->form->fill($this->defaultState());
        $this->editing = true;
    }

    public function backToList(): void
    {
        $this->editing = false;
        $this->widgetId = null;
    }

    public function deleteFromList(int $id): void
    {
        ReviewWidget::query()->where('workspace_id', $this->workspace()->id)->find($id)?->delete();

        Notification::make()->title(__('pages/review_widgets.deleted'))->success()->send();
    }

    /**
     * @return list<array{id: int, name: string, layout: string, active: bool, count: int}>
     */
    public function widgetsList(): array
    {
        return ReviewWidget::query()
            ->where('workspace_id', $this->workspace()->id)
            ->orderBy('id')
            ->get()
            ->map(fn (ReviewWidget $w): array => [
                'id' => $w->id,
                'name' => $w->name ?: __('pages/review_widgets.untitled'),
                'layout' => $w->layout(),
                'active' => (bool) $w->active,
                'count' => count($w->snapshotReviews()),
            ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultState(): array
    {
        return array_merge(ReviewWidget::defaultSettings(), [
            'name' => __('pages/review_widgets.default_name'),
            'active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function stateFromWidget(ReviewWidget $widget): array
    {
        return array_merge(ReviewWidget::defaultSettings(), $widget->settings, [
            'name' => $widget->name,
            'active' => (bool) $widget->active,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        // Vertical accordion of collapsible sections (Trustindex-style sidebar)
        // rather than horizontal tabs: the first section is open, the rest start
        // collapsed so the panel stays compact.
        return $schema
            ->statePath('data')
            ->components([
                ...$this->sourcesTab(),
                ...$this->layoutTab(),
                ...$this->cardTab(),
                ...$this->headerTab(),
                ...$this->styleTab(),
            ]);
    }

    /** @return array<int, mixed> */
    protected function sourcesTab(): array
    {
        return [
            Section::make(__('pages/review_widgets.tab_sources'))->compact()->collapsible()->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->label(__('pages/review_widgets.name'))->maxLength(120)->live(onBlur: true),
                    Toggle::make('active')->label(__('pages/review_widgets.active'))->inline(false)->live(),
                ]),
                CheckboxList::make('location_ids')
                    ->label(__('pages/review_widgets.locations'))
                    ->helperText(__('pages/review_widgets.locations_help'))
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->columns(2)->live(),
                Grid::make(3)->schema([
                    Select::make('min_rating')
                        ->label(__('pages/review_widgets.min_rating'))
                        ->options([5 => '5★', 4 => '4★ +', 3 => '3★ +', 2 => '2★ +', 1 => __('pages/review_widgets.any_rating')])
                        ->selectablePlaceholder(false)->live(),
                    Select::make('sort')
                        ->label(__('pages/review_widgets.sort'))
                        ->options([
                            'newest' => __('pages/review_widgets.sort_newest'),
                            'highest' => __('pages/review_widgets.sort_highest'),
                            'random' => __('pages/review_widgets.sort_random'),
                        ])->selectablePlaceholder(false)->live(),
                    TextInput::make('max_reviews')->label(__('pages/review_widgets.max_reviews'))->numeric()->minValue(1)->maxValue(60)->live(onBlur: true),
                ]),
                Toggle::make('require_text')->label(__('pages/review_widgets.require_text'))->inline(false)->live(),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    protected function layoutTab(): array
    {
        return [
            Section::make(__('pages/review_widgets.tab_layout'))->compact()->collapsible()->collapsed()->schema([
                Select::make('layout')
                    ->label(__('pages/review_widgets.layout'))
                    ->options([
                        'slider' => __('pages/review_widgets.layout_slider'),
                        'grid' => __('pages/review_widgets.layout_grid'),
                        'list' => __('pages/review_widgets.layout_list'),
                        'masonry' => __('pages/review_widgets.layout_masonry'),
                    ])->selectablePlaceholder(false)->live(),
                Grid::make(2)->schema([
                    TextInput::make('target_column_width')->label(__('pages/review_widgets.column_width'))->numeric()->suffix('px')->minValue(180)->maxValue(600)->live(onBlur: true),
                    TextInput::make('gap')->label(__('pages/review_widgets.gap'))->numeric()->suffix('px')->minValue(0)->maxValue(60)->live(onBlur: true),
                ]),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    protected function cardTab(): array
    {
        return [
            Section::make(__('pages/review_widgets.tab_card'))->compact()->collapsible()->collapsed()->schema([
                Grid::make(2)->schema([
                    Toggle::make('show_avatar')->label(__('pages/review_widgets.show_avatar'))->inline(false)->live(),
                    Toggle::make('show_rating')->label(__('pages/review_widgets.show_rating'))->inline(false)->live(),
                    Toggle::make('show_date')->label(__('pages/review_widgets.show_date'))->inline(false)->live(),
                    Toggle::make('show_reply')->label(__('pages/review_widgets.show_reply'))->inline(false)->live(),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('text_max_lines')->label(__('pages/review_widgets.text_max_lines'))->helperText(__('pages/review_widgets.text_max_lines_help'))->numeric()->minValue(0)->maxValue(30)->live(onBlur: true),
                    TextInput::make('rounded')->label(__('pages/review_widgets.rounded'))->numeric()->suffix('px')->minValue(0)->maxValue(40)->live(onBlur: true),
                ]),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    protected function headerTab(): array
    {
        return [
            Section::make(__('pages/review_widgets.tab_header'))->compact()->collapsible()->collapsed()->schema([
                Toggle::make('show_header')->label(__('pages/review_widgets.show_header'))->inline(false)->live(),
                TextInput::make('header_title')->label(__('pages/review_widgets.header_title'))->placeholder(fn (): string => $this->workspace()->name ?? '')->maxLength(120)->live(onBlur: true),
                Toggle::make('show_summary')->label(__('pages/review_widgets.show_summary'))->helperText(__('pages/review_widgets.show_summary_help'))->inline(false)->live(),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    protected function styleTab(): array
    {
        return [
            Section::make(__('pages/review_widgets.tab_style'))->compact()->collapsible()->collapsed()->schema([
                Grid::make(2)->schema([
                    Select::make('theme')->label(__('pages/review_widgets.theme'))
                        ->options(['light' => __('pages/review_widgets.theme_light'), 'dark' => __('pages/review_widgets.theme_dark')])
                        ->selectablePlaceholder(false)->live(),
                    ColorPicker::make('accent')->label(__('pages/review_widgets.accent'))->live(onBlur: true),
                    ColorPicker::make('card_background')->label(__('pages/review_widgets.card_background'))->live(onBlur: true),
                    ColorPicker::make('text_color')->label(__('pages/review_widgets.text_color'))->live(onBlur: true),
                ]),
                Toggle::make('branding')->label(__('pages/review_widgets.branding'))->inline(false)->live(),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    protected function settingsFromState(array $d): array
    {
        $settings = [];
        foreach (array_keys(ReviewWidget::defaultSettings()) as $key) {
            if (array_key_exists($key, $d)) {
                $settings[$key] = $d[$key];
            }
        }

        // Normalise the numeric/array fields coming from the form.
        $settings['location_ids'] = array_values(array_map('intval', (array) ($d['location_ids'] ?? [])));
        $settings['min_rating'] = (int) ($d['min_rating'] ?? 4);
        $settings['max_reviews'] = max(1, (int) ($d['max_reviews'] ?? 12));
        $settings['target_column_width'] = (int) ($d['target_column_width'] ?? 320);
        $settings['gap'] = (int) ($d['gap'] ?? 16);
        $settings['rounded'] = (int) ($d['rounded'] ?? 12);
        $settings['text_max_lines'] = (int) ($d['text_max_lines'] ?? 6);

        return $settings;
    }

    /** The transient widget the live preview renders (style is instant; the
     *  selected reviews come from the last saved snapshot). */
    public function previewWidget(): ReviewWidget
    {
        $d = $this->form->getRawState();

        $widget = new ReviewWidget([
            'workspace_id' => $this->workspace()->id,
            'token' => 'preview',
            'name' => $d['name'] ?? null,
            'settings' => $this->settingsFromState($d),
        ]);

        $saved = $this->widgetId !== null ? ReviewWidget::find($this->widgetId) : null;
        $snapshot = $saved?->snapshot ?? $this->demoSnapshot();

        // Reflect the Sources filters in the preview live, so toggling min
        // rating / order / max reviews visibly changes it before saving. (The
        // saved embed applies these when the snapshot is rebuilt.)
        $snapshot['reviews'] = $this->applyPreviewFilters($snapshot['reviews'] ?? [], $widget->settings);

        $widget->snapshot = $snapshot;
        $widget->setRelation('workspace', $this->workspace());

        return $widget;
    }

    /**
     * @param  array<int, array<string, mixed>>  $reviews
     * @param  array<string, mixed>  $settings
     * @return array<int, array<string, mixed>>
     */
    protected function applyPreviewFilters(array $reviews, array $settings): array
    {
        $minRating = (int) ($settings['min_rating'] ?? 4);
        $requireText = (bool) ($settings['require_text'] ?? true);
        $max = max(1, (int) ($settings['max_reviews'] ?? 12));

        $reviews = array_values(array_filter($reviews, function (array $r) use ($minRating, $requireText): bool {
            if ((int) ($r['rating'] ?? 0) < $minRating) {
                return false;
            }

            return ! $requireText || filled($r['text'] ?? null);
        }));

        // Order: 'random' is left in place to avoid reshuffling on every
        // keystroke; 'highest' sorts by rating, otherwise newest-first.
        if (($settings['sort'] ?? 'newest') === 'highest') {
            usort($reviews, fn (array $a, array $b): int => (int) $b['rating'] <=> (int) $a['rating']);
        }

        return array_slice($reviews, 0, $max);
    }

    public function previewMarkup(): HtmlString
    {
        return new HtmlString(view('widgets.embed', [
            'widget' => $this->previewWidget(),
            'jsonLd' => [],
        ])->render());
    }

    /** Placeholder reviews so a brand-new widget still previews. */
    protected function demoSnapshot(): array
    {
        $now = now();

        $mk = fn (int $offset, string $author, int $rating, string $text, ?string $reply = null): array => [
            'id' => -$offset - 1,
            'author' => $author,
            'rating' => $rating,
            'text' => $text,
            'reply' => $reply,
            'location' => null,
            'date' => $now->copy()->subDays($offset * 3)->translatedFormat('d M Y'),
            'date_iso' => $now->copy()->subDays($offset * 3)->toIso8601String(),
            'link' => null,
        ];

        return [
            'summary' => ['average' => 4.8, 'count' => 128],
            'reviews' => [
                $mk(0, 'Anna S.', 5, __('pages/review_widgets.demo_1')),
                $mk(1, 'Michael R.', 5, __('pages/review_widgets.demo_2'), __('pages/review_widgets.demo_reply')),
                $mk(2, 'Julia K.', 4, __('pages/review_widgets.demo_3')),
                $mk(3, 'David L.', 5, __('pages/review_widgets.demo_2')),
                $mk(4, 'Sophie M.', 3, __('pages/review_widgets.demo_3')),
                $mk(5, 'Thomas W.', 4, __('pages/review_widgets.demo_1')),
            ],
        ];
    }

    public function embedSnippet(): ?string
    {
        if ($this->widgetId === null) {
            return null;
        }

        return ReviewWidget::find($this->widgetId)?->embedSnippet();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('pages/review_widgets.back_to_list'))
                ->icon(Heroicon::OutlinedArrowLeft)->color('gray')
                ->visible(fn (): bool => $this->editing)
                ->action('backToList'),

            Action::make('newWidget')
                ->label(__('pages/review_widgets.new_widget'))
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => ! $this->editing)
                ->action('newWidget'),

            Action::make('save')
                ->label(__('pages/review_widgets.save_get_code'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => $this->editing)
                ->action('save'),

            Action::make('getCode')
                ->label(__('pages/review_widgets.get_code'))
                ->icon(Heroicon::OutlinedCodeBracket)->color('gray')
                ->visible(fn (): bool => $this->editing && $this->widgetId !== null)
                ->modalHeading(__('pages/review_widgets.embed_modal_heading'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('common.close'))
                ->modalContent(fn (): HtmlString => new HtmlString(
                    '<p style="margin-bottom:.75rem;">'.e(__('pages/review_widgets.embed_modal_desc')).'</p>'
                    .'<pre style="white-space:pre-wrap; word-break:break-all; background:#f4f4f6; border:1px solid #e5e7eb; border-radius:.5rem; padding:.75rem; font-size:.8rem;">'.e((string) $this->embedSnippet()).'</pre>'
                    .'<p style="margin-top:.75rem; font-weight:600;">'.e(__('pages/review_widgets.embed_modal_note')).'</p>'
                )),

            Action::make('deleteWidget')
                ->label(__('pages/review_widgets.delete'))
                ->icon(Heroicon::OutlinedTrash)->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->editing && $this->widgetId !== null)
                ->action(function (): void {
                    ReviewWidget::query()->where('workspace_id', $this->workspace()->id)->find($this->widgetId)?->delete();
                    Notification::make()->title(__('pages/review_widgets.deleted'))->success()->send();
                    $this->backToList();
                }),
        ];
    }

    public function save(): void
    {
        $d = $this->form->getState();

        $widget = ReviewWidget::updateOrCreate(
            ['id' => $this->widgetId],
            [
                'workspace_id' => $this->workspace()->id,
                'token' => $this->widgetId !== null
                    ? (ReviewWidget::find($this->widgetId)?->token ?? ReviewWidget::generateToken())
                    : ReviewWidget::generateToken(),
                'name' => $d['name'] ?? null,
                'active' => (bool) ($d['active'] ?? true),
                'settings' => $this->settingsFromState($this->form->getRawState()),
            ],
        );

        $this->widgetId = $widget->id;

        // Rebuild the snapshot now (we are already inside this workspace's
        // tenancy) so the preview and the live embed reflect the new selection.
        BuildReviewWidgetSnapshotJob::dispatchSync($widget->id);

        ActivityLogger::log('review_widget.updated', ['name' => $widget->name]);

        Notification::make()->title(__('pages/review_widgets.saved'))->success()->send();
    }
}
