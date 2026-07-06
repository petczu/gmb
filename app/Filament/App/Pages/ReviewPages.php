<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\ReviewPage;
use App\Models\ReviewPageStat;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use BackedEnum;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Configurator for the public "leave a review" collection pages: settings form
 * on the left, LIVE preview iframe on the right (email-templates pattern), QR
 * download and click analytics.
 */
class ReviewPages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'review-pages';

    protected string $view = 'filament.app.pages.review-pages';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?int $pageId = null;

    /** Language shown in the live preview (the srcdoc iframe can't navigate). */
    public string $previewLang = 'en';

    public static function getNavigationLabel(): string
    {
        return __('pages/review_pages.nav');
    }

    public function getTitle(): string
    {
        return __('pages/review_pages.title');
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

    /** false = list of pages (the landing view), true = editing one page. */
    public bool $editing = false;

    /** Shows the slug input for an existing page (hidden behind "edit"). */
    public bool $editingSlug = false;

    public function mount(): void
    {
        $hasPages = ReviewPage::query()->where('workspace_id', $this->workspace()->id)->exists();

        // First run: no pages yet → jump straight into the editor.
        if (! $hasPages) {
            $this->editing = true;
        }

        $this->form->fill($this->defaultState());
    }

    /** Open one page in the editor. */
    public function edit(int $id): void
    {
        $page = ReviewPage::query()->where('workspace_id', $this->workspace()->id)->findOrFail($id);

        $this->pageId = $page->id;
        $this->form->fill($this->stateFromPage($page));
        $this->editing = true;
        $this->editingSlug = false;
    }

    public function backToList(): void
    {
        $this->editing = false;
        $this->pageId = null;
    }

    /** Delete straight from the list (wire:confirm in the blade). */
    public function deleteFromList(int $id): void
    {
        ReviewPage::query()->where('workspace_id', $this->workspace()->id)->find($id)?->delete();

        Notification::make()->title(__('pages/review_pages.page_deleted'))->success()->send();
    }

    /**
     * The landing list: every page with its 30-day traffic.
     *
     * @return list<array{id: int, slug: string, url: string, custom_domain: ?string, active: bool, views: int, clicks: int}>
     */
    public function pagesList(): array
    {
        $pages = ReviewPage::query()
            ->where('workspace_id', $this->workspace()->id)
            ->orderBy('id')
            ->get();

        $stats = ReviewPageStat::query()
            ->whereIn('review_page_id', $pages->pluck('id'))
            ->where('day', '>=', now()->subDays(30)->toDateString())
            ->get()
            ->groupBy('review_page_id');

        return $pages->map(function (ReviewPage $page) use ($stats): array {
            $rows = $stats->get($page->id, collect());

            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'url' => $page->custom_domain ? 'https://'.$page->custom_domain : route('review-page.show', $page->slug),
                'custom_domain' => $page->custom_domain,
                'active' => (bool) $page->active,
                'views' => (int) $rows->where('metric', 'view')->sum('count'),
                'clicks' => (int) $rows->where('metric', '!=', 'view')->sum('count'),
            ];
        })->all();
    }

    /** Public URL of the page being edited (null while unsaved). */
    public function publicUrl(): ?string
    {
        if ($this->pageId === null) {
            return null;
        }

        $page = ReviewPage::find($this->pageId);

        return $page === null ? null : ($page->custom_domain ? 'https://'.$page->custom_domain : route('review-page.show', $page->slug));
    }

    /** Start a fresh page (persisted on Save). */
    public function newPage(): void
    {
        $this->pageId = null;
        $this->form->fill($this->defaultState());
        $this->editing = true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultState(): array
    {
        $workspace = $this->workspace();

        return [
            'slug' => ReviewPage::generateSlug($workspace->name ?? 'reviews'),
            'active' => true,
            'custom_domain' => null,
            'theme' => 'dark',
            'accent' => '#2d19ec',
            'languages' => ['en', 'de'],
            'use_workspace_logo' => true,
            'headline_en' => 'Leave a Review',
            'headline_de' => 'Bewertung abgeben',
            'subtitle_en' => 'Thank you for visiting! We would love to hear about your experience.',
            'subtitle_de' => 'Danke für deinen Besuch! Erzähl uns von deinem Erlebnis.',
            'targets' => [
                ['platform' => 'google', 'url' => '', 'enabled' => true],
                ['platform' => 'tripadvisor', 'url' => '', 'enabled' => false],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function stateFromPage(ReviewPage $page): array
    {
        $s = $page->settings;

        return [
            'slug' => $page->slug,
            'active' => $page->active,
            'custom_domain' => $page->custom_domain,
            'theme' => $s['theme'] ?? 'dark',
            'accent' => $s['accent'] ?? '#2d19ec',
            'languages' => $s['languages'] ?? ['en'],
            'use_workspace_logo' => ! empty($s['logo_url']),
            'headline_en' => $s['headline']['en'] ?? '',
            'headline_de' => $s['headline']['de'] ?? '',
            'subtitle_en' => $s['subtitle']['en'] ?? '',
            'subtitle_de' => $s['subtitle']['de'] ?? '',
            'targets' => array_map(fn (array $t): array => [
                'platform' => $t['platform'] ?? 'custom',
                'url' => $t['url'] ?? '',
                'enabled' => $t['enabled'] ?? true,
            ], (array) ($s['targets'] ?? [])),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('review_page_tabs')->tabs([
                    Tab::make(__('pages/review_pages.section_page'))->schema([
                        $this->pageSection(),
                    ]),
                    Tab::make(__('pages/review_pages.tab_style'))->schema([
                        $this->styleSection(),
                    ]),
                    Tab::make(__('pages/review_pages.tab_texts'))->schema([
                        $this->textsSection(),
                    ]),
                    Tab::make(__('pages/review_pages.section_targets'))->schema([
                        $this->targetsSection(),
                    ]),
                ])->persistTab(false),
            ]);
    }

    protected function pageSection(): Section
    {
        return Section::make(__('pages/review_pages.section_page'))
            ->compact()
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('slug')
                        ->label(__('pages/review_pages.slug'))
                        ->prefix('/r/')
                        ->required()->maxLength(64)
                        ->rules(['alpha_dash'])
                        // The full URL lives in "Public link" below; the raw slug
                        // input only appears for new pages or after "edit".
                        ->visible(fn (): bool => $this->pageId === null || $this->editingSlug)
                        ->live(onBlur: true),
                    Toggle::make('active')->label(__('pages/review_pages.active'))->inline(false)->live(),
                ]),
                // Full public link with a copy button (the slug input above
                // truncates the long prefix).
                Placeholder::make('public_link')
                    ->label(__('pages/review_pages.public_link'))
                    ->visible(fn (): bool => $this->publicUrl() !== null)
                    ->content(function (): HtmlString {
                        $url = (string) $this->publicUrl();

                        return new HtmlString(
                            '<div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">'
                            .'<code style="font-size:.85rem; background:#f4f4f6; border:1px solid #e5e7eb; border-radius:.5rem; padding:.35rem .6rem; word-break:break-all;">'.e($url).'</code>'
                            .'<button type="button" title="'.e(__('pages/review_pages.copy_link')).'"'
                            .' onclick="repunioCopy('.e(json_encode($url)).', this)"'
                            .' style="border:1px solid #e5e7eb; background:#fff; border-radius:.5rem; padding:.35rem .5rem; cursor:pointer; display:inline-flex; align-items:center; color:#374151;">'
                            .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1rem; height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>'
                            .'</button>'
                            .'<button type="button" wire:click="$set(\'editingSlug\', true)" title="'.e(__('pages/review_pages.edit_slug')).'"'
                            .' style="border:1px solid #e5e7eb; background:#fff; border-radius:.5rem; padding:.35rem .5rem; cursor:pointer; display:inline-flex; align-items:center; color:#374151;">'
                            .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1rem; height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>'
                            .'</button>'
                            .'</div>'
                        );
                    }),

                TextInput::make('custom_domain')
                    ->label(__('pages/review_pages.custom_domain'))
                    ->placeholder('review.your-business.com')
                    ->helperText(__('pages/review_pages.custom_domain_help', ['host' => parse_url((string) config('app.url'), PHP_URL_HOST)]))
                    ->maxLength(190)
                    ->live(onBlur: true),
            ]);
    }

    protected function styleSection(): Section
    {
        return Section::make(__('pages/review_pages.tab_style'))
            ->compact()
            ->schema([
                Grid::make(3)->schema([
                    Select::make('theme')
                        ->label(__('pages/review_pages.theme'))
                        ->options(['dark' => __('pages/review_pages.theme_dark'), 'light' => __('pages/review_pages.theme_light')])
                        ->selectablePlaceholder(false)->live(),
                    ColorPicker::make('accent')->label(__('pages/review_pages.accent'))->live(onBlur: true),
                    Toggle::make('use_workspace_logo')->label(__('pages/review_pages.use_logo'))->inline(false)->live(),
                ]),
                CheckboxList::make('languages')
                    ->label(__('pages/review_pages.languages'))
                    ->options(['en' => 'English', 'de' => 'Deutsch'])
                    ->columns(2)->required()->live(),
            ]);
    }

    protected function textsSection(): Section
    {
        return Section::make(__('pages/review_pages.tab_texts'))
            ->compact()
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('headline_en')->label(__('pages/review_pages.headline').' (EN)')->live(onBlur: true),
                    TextInput::make('headline_de')->label(__('pages/review_pages.headline').' (DE)')->live(onBlur: true),
                    TextInput::make('subtitle_en')->label(__('pages/review_pages.subtitle').' (EN)')->live(onBlur: true),
                    TextInput::make('subtitle_de')->label(__('pages/review_pages.subtitle').' (DE)')->live(onBlur: true),
                ]),
            ]);
    }

    protected function targetsSection(): Section
    {
        return Section::make(__('pages/review_pages.section_targets'))
            ->description(__('pages/review_pages.section_targets_desc'))
            ->compact()
            ->schema([
                Repeater::make('targets')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(12)->schema([
                            Select::make('platform')
                                ->hiddenLabel()
                                ->options(['google' => 'Google', 'tripadvisor' => 'TripAdvisor', 'custom' => __('pages/review_pages.platform_custom')])
                                ->selectablePlaceholder(false)
                                ->columnSpan(3)->live(),
                            TextInput::make('url')
                                ->hiddenLabel()
                                ->placeholder('https://…')
                                ->url()
                                ->columnSpan(7)->live(onBlur: true),
                            Toggle::make('enabled')->hiddenLabel()->inline(false)->default(true)->columnSpan(2)->live(),
                        ]),
                    ])
                    ->addActionLabel(__('pages/review_pages.add_target'))
                    ->reorderable()
                    ->live()
                    ->maxItems(5),
            ]);
    }

    /** The transient page used by the live preview (never persisted). */
    public function previewPage(): ReviewPage
    {
        $d = $this->form->getRawState();

        return new ReviewPage([
            'workspace_id' => $this->workspace()->id,
            'slug' => $d['slug'] ?? 'preview',
            'custom_domain' => null,
            'active' => true,
            'settings' => $this->settingsFromState($d),
        ]);
    }

    public function previewHtml(): string
    {
        $page = $this->previewPage();
        $languages = (array) ($page->settings['languages'] ?? ['en']);

        return view('review-page.show', [
            'page' => $page,
            'lang' => in_array($this->previewLang, $languages, true) ? $this->previewLang : ($languages[0] ?? 'en'),
            'languages' => $languages,
            // Inside the srcdoc iframe the built-in ?lang= links can't navigate,
            // so the configurator provides its own EN/DE toggle.
            'preview' => true,
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    protected function settingsFromState(array $d): array
    {
        $targets = [];
        foreach ((array) ($d['targets'] ?? []) as $t) {
            if (blank($t['url'] ?? null)) {
                continue;
            }
            $platform = in_array($t['platform'] ?? '', ReviewPage::PLATFORMS, true) ? $t['platform'] : 'custom';
            $key = $platform === 'custom' ? 'link-'.substr(md5((string) $t['url']), 0, 6) : $platform;
            $targets[] = [
                'key' => $key,
                'platform' => $platform,
                'url' => $t['url'],
                'enabled' => (bool) ($t['enabled'] ?? true),
            ];
        }

        return [
            'theme' => ($d['theme'] ?? 'dark') === 'light' ? 'light' : 'dark',
            'accent' => $d['accent'] ?? '#2d19ec',
            'languages' => array_values(array_intersect(['en', 'de'], (array) ($d['languages'] ?? ['en']))) ?: ['en'],
            'logo_url' => ! empty($d['use_workspace_logo']) ? $this->workspace()->logoUrl() : null,
            'headline' => ['en' => $d['headline_en'] ?? '', 'de' => $d['headline_de'] ?? ''],
            'subtitle' => ['en' => $d['subtitle_en'] ?? '', 'de' => $d['subtitle_de'] ?? ''],
            'targets' => $targets,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('pages/review_pages.back_to_list'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->visible(fn (): bool => $this->editing)
                ->action('backToList'),

            Action::make('newPage')
                ->label(__('pages/review_pages.new_page'))
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => ! $this->editing)
                ->action('newPage'),

            Action::make('deletePage')
                ->label(__('pages/review_pages.delete_page'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('pages/review_pages.delete_page_desc'))
                ->visible(fn (): bool => $this->editing && $this->pageId !== null)
                ->action(function (): void {
                    $page = ReviewPage::query()
                        ->where('workspace_id', $this->workspace()->id)
                        ->find($this->pageId);
                    $page?->delete();

                    ActivityLogger::log('review_page.updated', ['slug' => ($page?->slug ?? '').' (deleted)']);
                    Notification::make()->title(__('pages/review_pages.page_deleted'))->success()->send();

                    $next = ReviewPage::query()->where('workspace_id', $this->workspace()->id)->orderBy('id')->first();
                    $this->pageId = $next?->id;
                    $this->form->fill($next !== null ? $this->stateFromPage($next) : $this->defaultState());
                }),

            Action::make('save')
                ->label(__('common.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => $this->editing)
                ->action('save'),

            Action::make('qr')
                ->label(__('pages/review_pages.download_qr'))
                ->icon(Heroicon::OutlinedQrCode)
                ->color('gray')
                ->visible(fn (): bool => $this->editing && $this->pageId !== null)
                ->action(function () {
                    $page = ReviewPage::findOrFail($this->pageId);
                    $url = $page->custom_domain
                        ? 'https://'.$page->custom_domain
                        : route('review-page.show', $page->slug);

                    $writer = new Writer(new ImageRenderer(new RendererStyle(600, 2), new SvgImageBackEnd));
                    $svg = $writer->writeString($url);

                    return response()->streamDownload(
                        fn () => print ($svg),
                        'review-qr-'.$page->slug.'.svg',
                        ['Content-Type' => 'image/svg+xml'],
                    );
                }),

            Action::make('open')
                ->label(__('pages/review_pages.open_page'))
                ->icon(Heroicon::OutlinedLink)
                ->color('gray')
                ->visible(fn (): bool => $this->editing && $this->pageId !== null)
                ->url(fn (): string => route('review-page.show', ReviewPage::findOrFail($this->pageId)->slug))
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        $d = $this->form->getState();
        $slug = Str::slug((string) $d['slug']) ?: ReviewPage::generateSlug($this->workspace()->name ?? 'reviews');

        $slugTaken = ReviewPage::query()
            ->where('slug', $slug)
            ->when($this->pageId, fn ($q) => $q->whereKeyNot($this->pageId))
            ->exists();

        if ($slugTaken) {
            Notification::make()->title(__('pages/review_pages.slug_taken'))->danger()->send();

            return;
        }

        $domain = filled($d['custom_domain'] ?? null)
            ? strtolower(trim((string) $d['custom_domain']))
            : null;

        $page = ReviewPage::updateOrCreate(
            ['id' => $this->pageId],
            [
                'workspace_id' => $this->workspace()->id,
                'slug' => $slug,
                'custom_domain' => $domain,
                'active' => (bool) ($d['active'] ?? true),
                'settings' => $this->settingsFromState($this->form->getRawState()),
            ],
        );

        $this->pageId = $page->id;
        $this->editingSlug = false;

        ActivityLogger::log('review_page.updated', ['slug' => $page->slug]);

        Notification::make()->title(__('pages/review_pages.saved'))->success()->send();
    }

    /**
     * 30-day analytics for the header cards.
     *
     * @return array{views: int, clicks: int, ctr: int, perTarget: array<string, int>}
     */
    /**
     * Button label per target key, from the SAVED page (stats show zeros for
     * buttons that were never clicked).
     *
     * @return array<string, string>
     */
    public function targetLabels(): array
    {
        if ($this->pageId === null) {
            return [];
        }

        $names = ['google' => 'Google', 'tripadvisor' => 'TripAdvisor'];
        $labels = [];

        foreach ((array) (ReviewPage::find($this->pageId)?->targets() ?? []) as $t) {
            $labels[$t['key'] ?? ''] = $names[$t['platform'] ?? ''] ?? __('pages/review_pages.platform_custom');
        }

        return array_filter($labels, fn (string $k): bool => $k !== '', ARRAY_FILTER_USE_KEY);
    }

    public function analytics(): array
    {
        if ($this->pageId === null) {
            return ['views' => 0, 'clicks' => 0, 'ctr' => 0, 'perTarget' => []];
        }

        $rows = ReviewPageStat::query()
            ->where('review_page_id', $this->pageId)
            ->where('day', '>=', now()->subDays(30)->toDateString())
            ->get();

        $views = (int) $rows->where('metric', 'view')->sum('count');
        $perTarget = $rows->where('metric', '!=', 'view')
            ->groupBy('metric')
            ->map(fn ($group) => (int) $group->sum('count'))
            ->all();
        $clicks = array_sum($perTarget);

        return [
            'views' => $views,
            'clicks' => $clicks,
            'ctr' => $views > 0 ? (int) round($clicks / $views * 100) : 0,
            'perTarget' => $perTarget,
        ];
    }
}
