<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\ExternalCalendar;
use App\Models\ExternalCalendarEvent;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostNote;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Posts\IcsCalendarSync;
use App\Services\Posts\PostPublisher;
use App\Services\Zernio\ZernioRestClient;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Google Business Profile posts (updates, offers, events, photos), published
 * through Zernio's content publishing API. Zernio handles scheduling, so each
 * row here is history — not a local delivery queue.
 */
class Posts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'posts';

    protected string $view = 'filament.app.pages.posts';

    public static function getNavigationLabel(): string
    {
        return __('pages/posts.nav');
    }

    public function getTitle(): string
    {
        return __('pages/posts.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('publish_posts') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('publish_posts') ?? false;
    }

    public function isConfigured(): bool
    {
        return app(ZernioRestClient::class)->configured();
    }

    /** Create lives on the PAGE header so it shows in both calendar and list mode. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('pages/posts.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => $this->isConfigured())
                ->modalHeading(__('pages/posts.create_heading'))
                ->modalSubmitActionLabel(__('pages/posts.submit'))
                ->modalWidth(Width::SixExtraLarge)
                ->schema($this->composerSchema())
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('saveDraft', arguments: ['draft' => true])
                        ->label(__('pages/posts.save_draft'))
                        ->color('gray'),
                ])
                ->action(fn (array $data, array $arguments) => $this->publish($data, draft: (bool) ($arguments['draft'] ?? false))),
        ];
    }

    /** The composer form next to a live Google-style preview of the post. */
    protected function composerSchema(): array
    {
        return [
            Grid::make(['default' => 1, 'lg' => 2])
                ->schema([
                    Group::make($this->formSchema()),
                    Group::make([
                        Placeholder::make('post_preview')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => new HtmlString($this->previewHtml($get))),
                    ])->extraAttributes(['class' => 'lg:sticky lg:top-4']),
                ]),
        ];
    }

    /** 'calendar' | 'table', remembered per session. */
    public string $mode = 'calendar';

    /** Locations hidden from the calendar/list (checked = shown). @var list<int> */
    public array $hiddenLocations = [];

    /** @var array<int, string>|null cached location id => name map */
    protected ?array $locationNameMap = null;

    /** 'month' | 'week' inside calendar mode. */
    public string $calView = 'month';

    /** The month the calendar shows (Y-m). */
    public string $calMonth = '';

    /** Monday (Y-m-d) of the week the week view shows. */
    public string $calWeek = '';

    /** Prefill for the create modal's schedule field ("+ Post" on a day cell). */
    public ?string $prefillDate = null;

    public function mount(): void
    {
        $this->mode = in_array(session('posts_view_mode'), ['calendar', 'table'], true)
            ? session('posts_view_mode')
            : 'calendar';
        $this->calView = in_array(session('posts_cal_view'), ['month', 'week'], true)
            ? session('posts_cal_view')
            : 'month';
        $this->calMonth = now()->format('Y-m');
        $this->calWeek = now()->startOfWeek(CarbonImmutable::MONDAY)->format('Y-m-d');
        $this->hiddenNoteTags = array_values(array_filter((array) session('posts_hidden_note_tags', []), 'is_string'));
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['calendar', 'table'], true) ? $mode : 'calendar';
        session(['posts_view_mode' => $this->mode]);
    }

    /** Switch month/week, keeping the shown period roughly in place. */
    public function setCalView(string $view): void
    {
        $this->calView = in_array($view, ['month', 'week'], true) ? $view : 'month';
        session(['posts_cal_view' => $this->calView]);

        if ($this->calView === 'week') {
            $month = CarbonImmutable::createFromFormat('Y-m', $this->calMonth);
            $anchor = now()->isSameMonth($month) ? now()->toImmutable() : $month->startOfMonth();
            $this->calWeek = $anchor->startOfWeek(CarbonImmutable::MONDAY)->format('Y-m-d');
        } else {
            $this->calMonth = CarbonImmutable::createFromFormat('Y-m-d', $this->calWeek)->format('Y-m');
        }
    }

    public function prevPeriod(): void
    {
        $this->shiftPeriod(-1);
    }

    public function nextPeriod(): void
    {
        $this->shiftPeriod(1);
    }

    private function shiftPeriod(int $direction): void
    {
        if ($this->calView === 'week') {
            $this->calWeek = CarbonImmutable::createFromFormat('Y-m-d', $this->calWeek)->addWeeks($direction)->format('Y-m-d');
        } else {
            $this->calMonth = CarbonImmutable::createFromFormat('Y-m', $this->calMonth)->addMonths($direction)->format('Y-m');
        }
    }

    public function goToToday(): void
    {
        $this->calMonth = now()->format('Y-m');
        $this->calWeek = now()->startOfWeek(CarbonImmutable::MONDAY)->format('Y-m-d');
    }

    /** The period label for the calendar header, localized (week view adds the ISO week number). */
    public function calendarLabel(): string
    {
        if ($this->calView === 'week') {
            $week = CarbonImmutable::createFromFormat('Y-m-d', $this->calWeek);

            return $week->translatedFormat('M Y').' · W'.$week->isoWeek();
        }

        return CarbonImmutable::createFromFormat('Y-m', $this->calMonth)->translatedFormat('F Y');
    }

    /** "+ Post" on a day cell: open the composer with the schedule prefilled. */
    public function addPostOn(string $date): void
    {
        $day = CarbonImmutable::createFromFormat('Y-m-d', $date);
        $this->prefillDate = match (true) {
            $day->isToday() => now()->addHour()->startOfHour()->format('Y-m-d H:i'),
            $day->isFuture() => $day->setTime(10, 0)->format('Y-m-d H:i'),
            default => null,
        };

        $this->mountAction('create');
    }

    /** Consumed by the schedule field's default when the composer mounts. */
    public function pullPrefillDate(): ?string
    {
        $date = $this->prefillDate;
        $this->prefillDate = null;

        return $date;
    }

    /**
     * The visible calendar grid: full weeks (Mon–Sun), each day with its
     * posts, sticky notes and external-calendar events. A post lands on its
     * scheduled date, or the creation date for immediately-published ones.
     *
     * @return array<int, array<int, array{date: CarbonImmutable, inMonth: bool, isToday: bool, posts: Collection<int, Post>, notes: Collection<int, PostNote>, events: Collection<int, ExternalCalendarEvent>}>>
     */
    public function calendarWeeks(): array
    {
        if ($this->calView === 'week') {
            $gridStart = CarbonImmutable::createFromFormat('Y-m-d', $this->calWeek)->startOfDay();
            $gridEnd = $gridStart->addDays(6)->endOfDay();
            $month = null;
        } else {
            $month = CarbonImmutable::createFromFormat('Y-m', $this->calMonth)->startOfMonth();
            $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
            $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        }

        $posts = Post::query()
            ->where(function (Builder $q) use ($gridStart, $gridEnd): void {
                $q->whereBetween('scheduled_at', [$gridStart, $gridEnd])
                    ->orWhere(fn (Builder $qq) => $qq->whereNull('scheduled_at')->whereBetween('created_at', [$gridStart, $gridEnd]));
            })
            ->tap(fn (Builder $q) => $this->applyLocationFilter($q))
            ->orderBy('scheduled_at')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Post $post): string => ($post->scheduled_at ?? $post->created_at)->format('Y-m-d'));

        $notes = PostNote::query()
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('id')
            ->get()
            ->reject(fn (PostNote $note): bool => in_array($note->tag ?? self::UNTAGGED, $this->hiddenNoteTags, true))
            ->groupBy(fn (PostNote $note): string => $note->date->format('Y-m-d'));

        $events = ExternalCalendarEvent::query()
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->whereHas('calendar', fn (Builder $q) => $q->where('enabled', true))
            ->with('calendar:id,color')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($event): string => $event->date->format('Y-m-d'));

        $weeks = [];
        for ($day = $gridStart; $day->lessThanOrEqualTo($gridEnd); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');
            $weeks[$day->format('o-W')][] = [
                'date' => $day,
                'inMonth' => $month === null || $day->isSameMonth($month),
                'isToday' => $day->isToday(),
                'posts' => $posts->get($key, collect()),
                'notes' => $notes->get($key, collect()),
                'events' => $events->get($key, collect()),
            ];
        }

        return array_values($weeks);
    }

    /** Location id => name for the calendar/list location filter. */
    public function locationOptions(): array
    {
        return Location::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Toggle a location's visibility (checked = shown, like the note-tag filter). */
    public function toggleLocationFilter(int $locationId): void
    {
        $this->hiddenLocations = in_array($locationId, $this->hiddenLocations, true)
            ? array_values(array_diff($this->hiddenLocations, [$locationId]))
            : [...$this->hiddenLocations, $locationId];
    }

    /** Restrict posts to the locations still ticked (empty hidden set = all shown). */
    protected function applyLocationFilter(Builder $q): Builder
    {
        if ($this->hiddenLocations === []) {
            return $q;
        }

        $visible = array_values(array_diff(
            array_map('intval', array_keys($this->locationOptions())),
            $this->hiddenLocations,
        ));

        return $q->where(function (Builder $qq) use ($visible): void {
            if ($visible === []) {
                $qq->whereRaw('1 = 0');

                return;
            }
            foreach ($visible as $id) {
                $qq->orWhereJsonContains('location_ids', $id);
            }
        });
    }

    /** Short "which location" label for a post card (name, or "name +N"). */
    public function locationLabel(Post $post): ?string
    {
        $ids = array_values(array_map('intval', (array) ($post->location_ids ?? [])));
        if ($ids === []) {
            return null;
        }

        $this->locationNameMap ??= Location::query()->pluck('name', 'id')->all();

        $first = $this->locationNameMap[$ids[0]] ?? null;
        if ($first === null) {
            return null;
        }

        return count($ids) > 1
            ? __('pages/posts.location_plus', ['name' => $first, 'count' => count($ids) - 1])
            : $first;
    }

    // ── Sticky notes ────────────────────────────────────────────────────────

    public function addNote(string $date): void
    {
        PostNote::create([
            'date' => CarbonImmutable::createFromFormat('Y-m-d', $date)->toDateString(),
            'color' => 'yellow',
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()?->name,
        ]);
    }

    public function updateNote(int $noteId, string $field, ?string $value): void
    {
        if (! in_array($field, ['body', 'color', 'tag'], true)) {
            return;
        }

        if ($field === 'color' && ! array_key_exists((string) $value, PostNote::COLORS)) {
            return;
        }

        PostNote::query()->whereKey($noteId)->update([
            $field => filled($value) ? mb_substr($value, 0, $field === 'body' ? 2000 : 60) : null,
        ]);

        // A body save fires on BLUR, typically right before a click lands
        // somewhere else (e.g. "+ Note" on another day); re-rendering would
        // morph the DOM mid-click and swallow it, so we skip the repaint.
        // Tag/color DO repaint: a new tag has to surface the tag filter, and
        // a colour swap has to recolour the note immediately.
        if ($field === 'body') {
            $this->skipRender();
        }
    }

    public function deleteNote(int $noteId): void
    {
        PostNote::query()->whereKey($noteId)->delete();
    }

    // ── Drag & drop (notes + draft posts onto another day) ─────────────────

    public function moveNote(int $noteId, string $date): void
    {
        $day = $this->parseDay($date);
        if ($day === null) {
            return;
        }

        PostNote::query()->whereKey($noteId)->update(['date' => $day->toDateString()]);
    }

    /** Only drafts are movable: published/scheduled posts live on Google already. */
    public function moveDraft(int $postId, string $date): void
    {
        $day = $this->parseDay($date);
        $draft = Post::query()->whereKey($postId)->where('status', 'draft')->first();
        if ($day === null || $draft === null) {
            return;
        }

        // Keep the time of day, change only the date.
        $current = $draft->scheduled_at ?? $draft->created_at;
        $draft->forceFill([
            'scheduled_at' => $day->setTime((int) $current->format('H'), (int) $current->format('i')),
        ])->save();
    }

    private function parseDay(string $date): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** The note whose delete-confirmation modal is open. */
    public ?int $deletingNoteId = null;

    public function confirmDeleteNote(int $noteId): void
    {
        $this->deletingNoteId = $noteId;
        $this->mountAction('deleteNote');
    }

    public function deleteNoteAction(): Action
    {
        return Action::make('deleteNote')
            ->requiresConfirmation()
            ->modalHeading(__('pages/posts.note_delete'))
            ->modalDescription(__('pages/posts.note_delete_confirm'))
            ->modalSubmitActionLabel(__('pages/posts.note_delete'))
            ->color('danger')
            ->action(function (): void {
                if ($this->deletingNoteId !== null) {
                    $this->deleteNote($this->deletingNoteId);
                }
            });
    }

    /** Existing tags for the pick-or-create tag input. @return list<string> */
    public function noteTags(): array
    {
        return PostNote::query()->whereNotNull('tag')->distinct()->orderBy('tag')->pluck('tag')->all();
    }

    /** Sentinel for filtering notes that have no tag. */
    public const UNTAGGED = '__untagged';

    /** Note tags hidden from the calendar (plus UNTAGGED), session-persisted. */
    public array $hiddenNoteTags = [];

    public function toggleNoteTagFilter(string $tag): void
    {
        $this->hiddenNoteTags = in_array($tag, $this->hiddenNoteTags, true)
            ? array_values(array_diff($this->hiddenNoteTags, [$tag]))
            : [...$this->hiddenNoteTags, $tag];

        session(['posts_hidden_note_tags' => $this->hiddenNoteTags]);
    }

    // ── External calendars ─────────────────────────────────────────────────

    /** @return Collection<int, ExternalCalendar> */
    public function externalCalendars(): Collection
    {
        return ExternalCalendar::query()->orderBy('name')->get();
    }

    public function toggleCalendar(int $calendarId): void
    {
        $calendar = ExternalCalendar::find($calendarId);
        $calendar?->forceFill(['enabled' => ! $calendar->enabled])->save();
    }

    public function refreshCalendars(): void
    {
        $sync = app(IcsCalendarSync::class);
        $failed = $this->externalCalendars()->reject(fn (ExternalCalendar $c): bool => $sync->sync($c));

        if ($failed->isEmpty()) {
            Notification::make()->title(__('pages/posts.calendars_synced'))->success()->send();
        } else {
            Notification::make()
                ->title(__('pages/posts.calendars_sync_failed'))
                ->body($failed->pluck('name')->implode(', '))
                ->warning()
                ->send();
        }
    }

    public function deleteCalendar(int $calendarId): void
    {
        ExternalCalendar::query()->whereKey($calendarId)->delete();
    }

    /** The external calendar whose delete-confirmation modal is open. */
    public ?int $deletingCalendarId = null;

    public function confirmDeleteCalendar(int $calendarId): void
    {
        $this->deletingCalendarId = $calendarId;
        $this->mountAction('deleteCalendar');
    }

    public function deleteCalendarAction(): Action
    {
        return Action::make('deleteCalendar')
            ->requiresConfirmation()
            ->modalHeading(__('pages/posts.calendar_delete'))
            ->modalDescription(__('pages/posts.calendar_delete_confirm'))
            ->modalSubmitActionLabel(__('pages/posts.calendar_delete'))
            ->color('danger')
            ->action(function (): void {
                if ($this->deletingCalendarId !== null) {
                    $this->deleteCalendar($this->deletingCalendarId);
                }
            });
    }

    /** "Add external calendar" modal (name + public ICS URL + color). */
    public function addCalendarAction(): Action
    {
        return Action::make('addCalendar')
            ->modalHeading(__('pages/posts.calendar_add'))
            ->modalSubmitActionLabel(__('pages/posts.calendar_add_submit'))
            ->modalWidth(Width::Medium)
            ->schema([
                TextInput::make('name')
                    ->label(__('pages/posts.calendar_name'))
                    ->placeholder(__('pages/posts.calendar_name_placeholder'))
                    ->maxLength(100)
                    ->required(),

                TextInput::make('url')
                    ->label(__('pages/posts.calendar_url'))
                    ->url()
                    ->required()
                    ->helperText(__('pages/posts.calendar_url_helper')),

                Select::make('color')
                    ->label(__('pages/posts.calendar_color'))
                    ->options(collect(PostNote::COLORS)->mapWithKeys(
                        fn (array $c, string $key): array => [$key => '<span style="display:inline-flex; align-items:center; gap:.55rem;">'
                            .'<span style="display:inline-block; width:.75rem; height:.75rem; border-radius:999px; background:'.$c[1].';"></span>'
                            .e(__('pages/posts.color_'.$key)).'</span>'],
                    )->all())
                    ->allowHtml()
                    ->native(false)
                    ->default('green')
                    ->required()
                    ->selectablePlaceholder(false),
            ])
            ->action(function (array $data): void {
                $calendar = ExternalCalendar::create([
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'color' => $data['color'],
                    'enabled' => true,
                ]);

                if (app(IcsCalendarSync::class)->sync($calendar)) {
                    Notification::make()
                        ->title(__('pages/posts.calendar_added'))
                        ->body(trans_choice('pages/posts.calendar_events_count', $calendar->events()->count(), ['count' => $calendar->events()->count()]))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title(__('pages/posts.calendar_sync_error'))
                        ->body((string) $calendar->sync_error)
                        ->warning()
                        ->send();
                }
            });
    }

    /** The post whose details modal is open (calendar card click). */
    public ?int $viewingPostId = null;

    public function showPost(int $postId): void
    {
        $this->viewingPostId = $postId;

        // Drafts open in the editable composer; everything else is history and
        // gets the read-only details dialog.
        $this->mountAction(Post::find($postId)?->status === 'draft' ? 'editDraft' : 'viewPost');
    }

    /** Details modal for a calendar card. */
    public function viewPostAction(): Action
    {
        return Action::make('viewPost')
            ->modalHeading(fn (): string => __('pages/posts.type_'.(Post::find($this->viewingPostId)?->type ?? 'update')))
            // Hug the preview card (max-width 26rem) instead of a wide default.
            ->modalWidth(Width::Medium)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('pages/posts.close'))
            ->schema(fn (): array => [
                Placeholder::make('post_details')
                    ->hiddenLabel()
                    ->content(new HtmlString($this->postDetailsHtml((int) $this->viewingPostId))),
            ])
            ->extraModalFooterActions(fn (): array => [
                Action::make('duplicateDraft')
                    ->label(__('pages/posts.duplicate_draft'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn () => $this->duplicateAsDraft((int) $this->viewingPostId))
                    ->cancelParentActions(),
            ]);
    }

    /** Copy any post (including an imported Google one) into a fresh draft. */
    public function duplicateAsDraft(int $postId): void
    {
        $post = Post::find($postId);
        if ($post === null) {
            return;
        }

        Post::create([
            'type' => in_array($post->type, ['update', 'offer', 'event', 'photo'], true) ? $post->type : 'update',
            'caption' => $post->caption,
            'title' => $post->title,
            'image_url' => $post->image_url,
            'cta_type' => $post->cta_type,
            'cta_url' => $post->cta_url,
            'photo_category' => $post->photo_category,
            'starts_at' => $post->starts_at,
            'ends_at' => $post->ends_at,
            'voucher_code' => $post->voucher_code,
            'redeem_url' => $post->redeem_url,
            'terms_url' => $post->terms_url,
            'location_ids' => $post->location_ids ?? [],
            // source_ids is a NOT NULL json column; a fresh draft hasn't been
            // sent anywhere yet, so it starts empty.
            'source_ids' => [],
            'status' => 'draft',
            'origin' => 'app',
        ]);

        Notification::make()->title(__('pages/posts.duplicated_draft'))->success()->send();
    }

    /** Drafts reopen in the full composer: publish, keep as draft, or discard. */
    public function editDraftAction(): Action
    {
        return Action::make('editDraft')
            ->modalHeading(__('pages/posts.draft_heading'))
            ->modalSubmitActionLabel(__('pages/posts.submit'))
            ->modalWidth(Width::SixExtraLarge)
            ->schema($this->composerSchema())
            ->fillForm(fn (): array => $this->draftFormState())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('saveDraft', arguments: ['draft' => true])
                    ->label(__('pages/posts.save_draft'))
                    ->color('gray'),
                Action::make('deleteDraft')
                    ->label(__('pages/posts.draft_delete'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('pages/posts.draft_delete_desc'))
                    ->action(function (): void {
                        Post::query()->whereKey($this->viewingPostId)->where('status', 'draft')->delete();
                        Notification::make()->title(__('pages/posts.draft_deleted'))->success()->send();
                    })
                    ->cancelParentActions(),
            ])
            ->action(function (array $data, array $arguments): void {
                $draft = Post::query()->whereKey($this->viewingPostId)->where('status', 'draft')->first();
                $this->publish($data, draft: (bool) ($arguments['draft'] ?? false), existing: $draft);
            });
    }

    /** @return array<string, mixed> */
    private function draftFormState(): array
    {
        $post = Post::find($this->viewingPostId);

        if ($post === null) {
            return [];
        }

        return [
            'type' => $post->type,
            'locations' => $post->location_ids ?? [],
            'caption' => $post->caption,
            'image' => $this->imagePathFromUrl($post->image_url),
            'title' => $post->title,
            'starts_at' => $post->starts_at?->format('Y-m-d H:i'),
            'ends_at' => $post->ends_at?->format('Y-m-d H:i'),
            'voucher_code' => $post->voucher_code,
            'redeem_url' => $post->redeem_url,
            'terms_url' => $post->terms_url,
            'cta_type' => $post->cta_type,
            'cta_url' => $post->cta_url,
            'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
        ];
    }

    /** Reverse of url(Storage::url(...)) so FileUpload can re-hydrate a draft image. */
    private function imagePathFromUrl(?string $url): ?string
    {
        $path = (string) parse_url((string) $url, PHP_URL_PATH);

        return str_contains($path, '/storage/') ? Str::after($path, '/storage/') : null;
    }

    private function postDetailsHtml(int $postId): string
    {
        $post = Post::find($postId);
        if ($post === null) {
            return '';
        }

        $when = $post->scheduled_at ?? $post->created_at;
        $statusColors = ['published' => '#16a34a', 'scheduled' => '#0ea5e9', 'failed' => '#dc2626', 'in_progress' => '#d97706', 'draft' => '#9ca3af'];

        $dates = null;
        if (in_array($post->type, ['offer', 'event'], true) && ($post->starts_at || $post->ends_at)) {
            $fmt = fn ($v): ?string => $v ? CarbonImmutable::parse($v)->translatedFormat('M j') : null;
            $dates = trim(($fmt($post->starts_at) ?? '…').' – '.($fmt($post->ends_at) ?? '…'));
        }
        $cta = (string) ($post->cta_type ?? '');
        if ($post->type === 'offer') {
            $cta = 'learn_more';
        }

        // Status meta line, then the same Google-style card as the composer.
        $html = '<div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.7rem; font-size:.8rem; color:#6b7280;">'
            .'<span style="display:inline-block; width:.55rem; height:.55rem; border-radius:999px; background:'.($statusColors[$post->status] ?? '#9ca3af').';"></span>'
            .e(__('pages/posts.status_'.$post->status))
            .' · '.e($when->translatedFormat('D, j M Y · H:i'))
            .' · '.e(trans_choice('pages/posts.location_count', count($post->location_ids ?? []), ['count' => count($post->location_ids ?? [])]))
            .'</div>';

        $html .= $this->googlePreviewCard([
            'name' => $this->businessNameLabel($post->location_ids ?? []),
            'date' => $when->translatedFormat('M j, Y'),
            'logoUrl' => $this->previewLogoUrl($post->location_ids ?? []),
            'imageUrl' => filled($post->image_url) ? $post->image_url : null,
            'title' => $post->title,
            'dates' => $dates,
            'caption' => $post->caption,
            'captionPlaceholder' => false,
            'voucher' => $post->type === 'offer' ? $post->voucher_code : null,
            'cta' => filled($cta) ? $cta : null,
        ]);

        if (filled($post->error)) {
            $html .= '<div style="margin-top:.8rem; padding:.6rem .8rem; border-radius:.5rem; background:#fef2f2; color:#991b1b; font-size:.85rem;">'.e($post->error).'</div>';
        }

        return $html;
    }

    /** @param  array<int, int|string>  $locationIds */
    private function businessNameLabel(array $locationIds): string
    {
        $ids = array_values(array_map('intval', $locationIds));
        $names = Location::query()->whereIn('id', $ids)->orderBy('name')->pluck('name');
        $first = $names->first() ?? __('pages/posts.preview_business');

        return $names->count() > 1 ? $first.' +'.($names->count() - 1) : (string) $first;
    }

    private function workspaceLogoUrl(): ?string
    {
        $workspaceId = session('current_workspace_id');

        return $workspaceId ? Workspace::find($workspaceId)?->logoUrl() : null;
    }

    /**
     * Logo for the preview card: the first selected location's own logo, or the
     * workspace logo when the location has none.
     *
     * @param  array<int, int|string>  $locationIds
     */
    private function previewLogoUrl(array $locationIds): ?string
    {
        $ids = array_values(array_map('intval', $locationIds));

        foreach (Location::query()->whereIn('id', $ids)->orderBy('name')->get() as $location) {
            if (($url = $location->logoUrl()) !== null) {
                return $url;
            }
        }

        return $this->workspaceLogoUrl();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Post::query()->tap(fn (Builder $q) => $this->applyLocationFilter($q)))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/posts.empty'))
            ->emptyStateDescription(__('pages/posts.empty_desc'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('pages/posts.col_created'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('pages/posts.col_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('pages/posts.type_'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'offer' => 'warning',
                        'event' => 'info',
                        'photo' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('caption')
                    ->label(__('pages/posts.col_caption'))
                    ->limit(60)
                    ->placeholder('—')
                    ->tooltip(fn (Post $record): ?string => $record->caption),

                TextColumn::make('location_ids')
                    ->label(__('pages/posts.col_locations'))
                    ->state(fn (Post $record): string => (string) count($record->location_ids ?? [])),

                TextColumn::make('status')
                    ->label(__('pages/posts.col_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('pages/posts.status_'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'info',
                        'failed' => 'danger',
                        'draft' => 'gray',
                        default => 'warning',
                    })
                    ->tooltip(fn (Post $record): ?string => $record->error),

                TextColumn::make('scheduled_at')
                    ->label(__('pages/posts.col_scheduled'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('pages/posts.col_status'))
                    ->options([
                        'published' => __('pages/posts.status_published'),
                        'scheduled' => __('pages/posts.status_scheduled'),
                        'in_progress' => __('pages/posts.status_in_progress'),
                        'failed' => __('pages/posts.status_failed'),
                        'draft' => __('pages/posts.status_draft'),
                    ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('pages/posts.view'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (Post $record): string => __('pages/posts.type_'.$record->type))
                    ->modalWidth(Width::Medium)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('pages/posts.close'))
                    ->schema(fn (Post $record): array => [
                        Placeholder::make('post_details')
                            ->hiddenLabel()
                            ->content(new HtmlString($this->postDetailsHtml($record->id))),
                    ])
                    ->extraModalFooterActions(fn (Post $record): array => [
                        Action::make('duplicateDraft')
                            ->label(__('pages/posts.duplicate_draft'))
                            ->icon(Heroicon::OutlinedDocumentDuplicate)
                            ->action(fn () => $this->duplicateAsDraft($record->id))
                            ->cancelParentActions(),
                    ]),

                Action::make('delete')
                    ->label(__('pages/posts.delete'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('pages/posts.delete_desc'))
                    ->action(function (Post $record): void {
                        $record->delete();
                        Notification::make()->title(__('pages/posts.deleted'))->success()->send();
                    }),
            ])
            ->headerActions([]);
    }

    /**
     * @return array<int, Field>
     */
    protected function formSchema(): array
    {
        $isOfferOrEvent = fn (Get $get): bool => in_array($get('type'), ['offer', 'event'], true);

        return [
            Select::make('type')
                ->label(__('pages/posts.field_type'))
                // Zernio's native API models a photo post as a STANDARD update
                // with an image, so only the three real GBP topic types remain.
                ->options(collect(['update', 'offer', 'event'])->mapWithKeys(
                    fn (string $t): array => [$t => __('pages/posts.type_'.$t)],
                )->all())
                ->default('update')
                ->required()
                ->live()
                ->selectablePlaceholder(false),

            Select::make('locations')
                ->label(__('pages/posts.field_locations'))
                ->multiple()
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): array => Location::query()->pluck('id')->all())
                ->required(),

            Textarea::make('caption')
                ->label(__('pages/posts.field_caption'))
                ->rows(4)
                ->maxLength(1500)
                ->required()
                ->live(debounce: 600),

            FileUpload::make('image')
                ->label(__('pages/posts.field_image'))
                ->image()
                // Resize down in the browser BEFORE upload: keeps the file small
                // (well under any server upload_max_filesize / body limit, which
                // is what leaves big uploads stuck mid-progress) and matches the
                // resolution Google actually uses for post images.
                ->imageResizeMode('contain')
                ->imageResizeUpscale(false)
                ->imageResizeTargetWidth('1600')
                ->imageResizeTargetHeight('1600')
                ->disk('uploads')
                ->directory('posts')
                ->maxSize(4096)
                ->live()
                ->helperText(__('pages/posts.field_image_helper')),

            TextInput::make('title')
                ->label(__('pages/posts.field_title'))
                ->maxLength(58)
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent)
                ->live(debounce: 600),

            DateTimePicker::make('starts_at')
                ->label(__('pages/posts.field_starts'))
                ->seconds(false)
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent)
                ->live(),

            DateTimePicker::make('ends_at')
                ->label(__('pages/posts.field_ends'))
                ->seconds(false)
                ->after('starts_at')
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent)
                ->live(),

            TextInput::make('voucher_code')
                ->label(__('pages/posts.field_voucher'))
                ->maxLength(58)
                ->visible(fn (Get $get): bool => $get('type') === 'offer')
                ->live(debounce: 600),

            TextInput::make('redeem_url')
                ->label(__('pages/posts.field_redeem_url'))
                ->url()
                ->visible(fn (Get $get): bool => $get('type') === 'offer'),

            TextInput::make('terms_url')
                ->label(__('pages/posts.field_terms_url'))
                ->url()
                ->visible(fn (Get $get): bool => $get('type') === 'offer'),

            Select::make('cta_type')
                ->label(__('pages/posts.field_cta'))
                ->options(collect(Post::CTA_TYPES)->mapWithKeys(
                    fn (string $t): array => [$t => __('pages/posts.cta_'.$t)],
                )->all())
                ->placeholder(__('pages/posts.cta_none'))
                ->live()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['update', 'event'], true)),

            TextInput::make('cta_url')
                ->label(__('pages/posts.field_cta_url'))
                ->url()
                ->required(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call')
                ->visible(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call'
                    && in_array($get('type'), ['update', 'event'], true)),

            DateTimePicker::make('scheduled_at')
                ->label(__('pages/posts.field_schedule'))
                ->seconds(false)
                ->minDate(now())
                ->default(fn (): ?string => $this->pullPrefillDate())
                ->helperText(__('pages/posts.field_schedule_helper')),
        ];
    }

    /** Live preview of the composed post, styled like the card on Google Maps. */
    /**
     * The Google-Maps-style post card from normalized data. Shared by the
     * read-only post view (postDetailsHtml) so it matches the composer preview.
     *
     * @param  array{name:string,date:string,logoUrl:?string,imageUrl:?string,title:?string,dates:?string,caption:?string,captionPlaceholder?:bool,voucher:?string,cta:?string}  $d
     */
    private function googlePreviewCard(array $d): string
    {
        $name = (string) ($d['name'] ?? '');
        $logoUrl = $d['logoUrl'] ?? null;
        $imageUrl = $d['imageUrl'] ?? null;

        $avatar = $logoUrl !== null
            ? '<img src="'.e($logoUrl).'" alt="" style="width:2.4rem; height:2.4rem; border-radius:999px; object-fit:cover;">'
            : '<span style="display:inline-flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem; border-radius:999px; background:#202124; color:#fff; font-weight:700;">'.e(mb_strtoupper(mb_substr($name, 0, 1))).'</span>';

        $html = '<div style="max-width:26rem; border:1px solid rgb(0 0 0 / .08); border-radius:.75rem; overflow:hidden; background:#fff; color:#202124; box-shadow:0 1px 3px rgb(0 0 0 / .1);">';

        $html .= '<div style="display:flex; align-items:center; gap:.65rem; padding:.75rem .9rem;">'
            .'<span style="position:relative; flex:none; line-height:0;">'.$avatar
            .'<svg viewBox="0 0 24 24" fill="#1a73e8" style="position:absolute; right:-.15rem; bottom:-.15rem; width:.95rem; height:.95rem; background:#fff; border-radius:999px;"><path d="M12 2 9.19 4.63l-3.83.44-.44 3.83L2.29 11.7l2.63 2.81-.44 3.83 3.83.44L11.12 21.7l2.81-2.63 3.83.44.44-3.83 2.63-2.81-2.63-2.81.44-3.83-3.83-.44L12 2zm-1.4 13.3-2.9-2.9 1.06-1.06 1.84 1.83 4.64-4.63 1.06 1.06-5.7 5.7z"/></svg>'
            .'</span>'
            .'<span style="flex:1; min-width:0;">'
            .'<span style="display:block; font-weight:700; font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.e($name).'</span>'
            .'<span style="display:block; font-size:.76rem; color:#5f6368;">'.e((string) ($d['date'] ?? '')).'</span>'
            .'</span>'
            .'<span style="flex:none; display:inline-flex; align-items:center; gap:.7rem; color:#5f6368;">'
            .'<svg style="width:1.05rem; height:1.05rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>'
            .'<svg style="width:1.05rem; height:1.05rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/></svg>'
            .'</span>'
            .'</div>';

        if ($imageUrl !== null) {
            $html .= '<img src="'.e($imageUrl).'" alt="" style="display:block; width:100%; aspect-ratio:2/1; object-fit:cover;">';
        } else {
            $html .= '<div style="width:100%; aspect-ratio:2/1; background:repeating-linear-gradient(45deg,#f3f4f6,#f3f4f6 12px,#e5e7eb 12px,#e5e7eb 24px); display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:.8rem;">'.e(__('pages/posts.preview_no_image')).'</div>';
        }

        $html .= '<div style="padding:.9rem .9rem .35rem;">';

        if (filled($d['title'] ?? null)) {
            $html .= '<div style="font-weight:700; font-size:.95rem; margin-bottom:.25rem;">'.e((string) $d['title']).'</div>';
        }
        if (filled($d['dates'] ?? null)) {
            $html .= '<div style="font-size:.8rem; color:#5f6368; margin-bottom:.35rem;">'.e((string) $d['dates']).'</div>';
        }
        if (filled($d['caption'] ?? null)) {
            // Collapse the 3+ blank lines Google/imported posts often carry.
            $caption = (string) preg_replace('/\n{3,}/', "\n\n", (string) $d['caption']);
            $html .= '<div style="font-size:.9rem; line-height:1.55; white-space:pre-wrap; word-break:break-word;">'.e(Str::limit($caption, 600)).'</div>';
        } elseif (! empty($d['captionPlaceholder'])) {
            $html .= '<div style="font-size:.9rem; color:#c0c3c9;">'.e(__('pages/posts.preview_placeholder')).'</div>';
        }

        if (filled($d['voucher'] ?? null)) {
            $html .= '<div style="margin-top:.7rem; padding:.5rem .7rem; border:1px dashed #9ca3af; border-radius:.5rem; text-align:center; font-family:monospace; font-size:.85rem; letter-spacing:.1em;">'.e((string) $d['voucher']).'</div>';
        }

        $html .= '</div>';

        $cta = (string) ($d['cta'] ?? '');
        if (filled($cta)) {
            $html .= '<div style="margin-top:.55rem; border-top:1px solid rgb(0 0 0 / .07); padding:.75rem; text-align:center;">'
                .'<span style="color:#0d766e; font-weight:600; font-size:.9rem;">'.e(__('pages/posts.cta_'.$cta)).'</span>'
                .'</div>';
        } else {
            $html .= '<div style="height:.55rem;"></div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function previewHtml(Get $get): string
    {
        $locationIds = array_map('intval', (array) $get('locations'));
        $names = Location::query()->whereIn('id', $locationIds)->orderBy('name')->pluck('name');
        $name = $names->first() ?? __('pages/posts.preview_business');
        $extra = $names->count() > 1 ? ' +'.($names->count() - 1) : '';

        $logoUrl = $this->previewLogoUrl($locationIds);

        $image = $get('image');
        $image = is_array($image) ? collect($image)->first() : $image;
        $imageUrl = match (true) {
            $image instanceof TemporaryUploadedFile => $image->temporaryUrl(),
            is_string($image) && filled($image) => url(Storage::disk('uploads')->url($image)),
            default => null,
        };

        $type = (string) $get('type');
        $dates = null;
        if (in_array($type, ['offer', 'event'], true) && (filled($get('starts_at')) || filled($get('ends_at')))) {
            $format = fn ($v): ?string => filled($v) ? CarbonImmutable::parse($v)->translatedFormat('M j') : null;
            $dates = trim(($format($get('starts_at')) ?? '…').' – '.($format($get('ends_at')) ?? '…'));
        }

        $postDate = filled($get('scheduled_at'))
            ? CarbonImmutable::parse((string) $get('scheduled_at'))->translatedFormat('M j, Y')
            : now()->translatedFormat('M j, Y');

        $html = '<div style="font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; margin-bottom:.5rem;">'
            .e(__('pages/posts.preview_label')).'</div>';
        $html .= '<div style="max-width:26rem; border:1px solid rgb(0 0 0 / .08); border-radius:.75rem; overflow:hidden; background:#fff; color:#202124; box-shadow:0 1px 3px rgb(0 0 0 / .1);">';

        // Header: logo with verified badge, name + date, share/menu icons.
        $avatar = $logoUrl !== null
            ? '<img src="'.e($logoUrl).'" alt="" style="width:2.4rem; height:2.4rem; border-radius:999px; object-fit:cover;">'
            : '<span style="display:inline-flex; align-items:center; justify-content:center; width:2.4rem; height:2.4rem; border-radius:999px; background:#202124; color:#fff; font-weight:700;">'.e(mb_strtoupper(mb_substr((string) $name, 0, 1))).'</span>';

        $html .= '<div style="display:flex; align-items:center; gap:.65rem; padding:.75rem .9rem;">'
            .'<span style="position:relative; flex:none; line-height:0;">'.$avatar
            .'<svg viewBox="0 0 24 24" fill="#1a73e8" style="position:absolute; right:-.15rem; bottom:-.15rem; width:.95rem; height:.95rem; background:#fff; border-radius:999px;"><path d="M12 2 9.19 4.63l-3.83.44-.44 3.83L2.29 11.7l2.63 2.81-.44 3.83 3.83.44L11.12 21.7l2.81-2.63 3.83.44.44-3.83 2.63-2.81-2.63-2.81.44-3.83-3.83-.44L12 2zm-1.4 13.3-2.9-2.9 1.06-1.06 1.84 1.83 4.64-4.63 1.06 1.06-5.7 5.7z"/></svg>'
            .'</span>'
            .'<span style="flex:1; min-width:0;">'
            .'<span style="display:block; font-weight:700; font-size:.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.e($name.$extra).'</span>'
            .'<span style="display:block; font-size:.76rem; color:#5f6368;">'.e($postDate).'</span>'
            .'</span>'
            .'<span style="flex:none; display:inline-flex; align-items:center; gap:.7rem; color:#5f6368;">'
            .'<svg style="width:1.05rem; height:1.05rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/></svg>'
            .'<svg style="width:1.05rem; height:1.05rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 5.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Z"/></svg>'
            .'</span>'
            .'</div>';

        if ($imageUrl !== null) {
            $html .= '<img src="'.e($imageUrl).'" alt="" style="display:block; width:100%; aspect-ratio:2/1; object-fit:cover;">';
        } else {
            $html .= '<div style="width:100%; aspect-ratio:2/1; background:repeating-linear-gradient(45deg,#f3f4f6,#f3f4f6 12px,#e5e7eb 12px,#e5e7eb 24px); display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:.8rem;">'.e(__('pages/posts.preview_no_image')).'</div>';
        }

        $html .= '<div style="padding:.9rem .9rem .35rem;">';

        if (filled($get('title'))) {
            $html .= '<div style="font-weight:700; font-size:.95rem; margin-bottom:.25rem;">'.e((string) $get('title')).'</div>';
        }
        if ($dates !== null) {
            $html .= '<div style="font-size:.8rem; color:#5f6368; margin-bottom:.35rem;">'.e($dates).'</div>';
        }
        if (filled($get('caption'))) {
            $html .= '<div style="font-size:.9rem; line-height:1.55; white-space:pre-wrap; word-break:break-word;">'.e(Str::limit((string) $get('caption'), 600)).'</div>';
        } else {
            $html .= '<div style="font-size:.9rem; color:#c0c3c9;">'.e(__('pages/posts.preview_placeholder')).'</div>';
        }

        if ($type === 'offer' && filled($get('voucher_code'))) {
            $html .= '<div style="margin-top:.7rem; padding:.5rem .7rem; border:1px dashed #9ca3af; border-radius:.5rem; text-align:center; font-family:monospace; font-size:.85rem; letter-spacing:.1em;">'.e((string) $get('voucher_code')).'</div>';
        }

        $html .= '</div>';

        // Centered CTA above the card's bottom edge, like the Maps card.
        $cta = (string) $get('cta_type');
        if ($type === 'offer') {
            $cta = 'learn_more';
        }
        if (filled($cta)) {
            $html .= '<div style="margin-top:.55rem; border-top:1px solid rgb(0 0 0 / .07); padding:.75rem; text-align:center;">'
                .'<span style="color:#0d766e; font-weight:600; font-size:.9rem;">'.e(__('pages/posts.cta_'.$cta)).'</span>'
                .'</div>';
        } else {
            $html .= '<div style="height:.55rem;"></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Publish (or keep as a draft) the composer's post. $existing is set when
     * an earlier draft is being republished or re-saved.
     *
     * @param  array<string, mixed>  $data
     */
    protected function publish(array $data, bool $draft = false, ?Post $existing = null): void
    {
        $locations = Location::query()->whereIn('id', $data['locations'] ?? [])->get();

        if ($locations->isEmpty()) {
            Notification::make()->title(__('pages/posts.no_locations'))->danger()->send();

            return;
        }

        // Native posting targets the Zernio account + GBP location ids the
        // locations were connected with — no extra id mapping.
        $unmatched = $locations->filter(fn (Location $l): bool => blank($l->zernio_account_id) || blank($l->external_id));

        if (! $draft && $unmatched->isNotEmpty()) {
            Notification::make()
                ->title(__('pages/posts.unmatched'))
                ->body($unmatched->pluck('name')->implode(', '))
                ->danger()
                ->send();

            return;
        }

        $attributes = [
            'type' => $data['type'],
            'caption' => $data['caption'] ?? null,
            'title' => $data['title'] ?? null,
            'cta_type' => $data['cta_type'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'image_url' => filled($data['image'] ?? null)
                ? url(Storage::disk('uploads')->url($data['image']))
                : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'voucher_code' => $data['voucher_code'] ?? null,
            'redeem_url' => $data['redeem_url'] ?? null,
            'terms_url' => $data['terms_url'] ?? null,
            'location_ids' => $locations->pluck('id')->all(),
            'source_ids' => $locations->pluck('external_id')->all(),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $draft ? 'draft' : 'in_progress',
        ];

        if ($existing !== null) {
            $existing->update($attributes);
            $post = $existing;
        } else {
            $post = Post::create($attributes + [
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()?->name,
            ]);
        }

        if ($draft) {
            Notification::make()->title(__('pages/posts.draft_saved'))->success()->send();

            return;
        }

        app(PostPublisher::class)->publish($post, $locations);
        $post->refresh();

        if ($post->status === 'failed') {
            Notification::make()
                ->title(__('pages/posts.publish_failed'))
                ->body((string) $post->error)
                ->danger()
                ->send();

            return;
        }

        ActivityLogger::log(
            $post->status === 'scheduled' ? 'post.scheduled' : 'post.published',
            ['type' => $post->type, 'locations' => count($post->location_ids)],
            $post,
        );

        Notification::make()
            ->title($post->status === 'scheduled' ? __('pages/posts.scheduled_ok') : __('pages/posts.published_ok'))
            ->success()
            ->send();
    }
}
