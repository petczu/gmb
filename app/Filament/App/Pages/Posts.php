<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\ActivityEntry;
use App\Models\ExternalCalendar;
use App\Models\ExternalCalendarEvent;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLabel;
use App\Models\PostNote;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Posts\IcsCalendarSync;
use App\Services\Posts\PostCommentNotifier;
use App\Services\Posts\PostPublisher;
use App\Services\Zernio\ZernioRestClient;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Google Business Profile posts (updates, offers, events, photos), published
 * through Zernio's content publishing API. Zernio handles scheduling, so each
 * row here is history — not a local delivery queue.
 */
class Posts extends Page implements HasTable
{
    use InteractsWithTable;
    use WithFileUploads;

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

    /** Comments dialog for the current post: the thread plus an inline form to
     *  add a comment with attachments and @-mentions of workspace members.
     *  Mounted from the post dialogs (a top-level, non-nested modal). */
    public function commentsAction(): Action
    {
        return Action::make('comments')
            ->modalHeading(__('pages/posts.comments'))
            ->modalSubmitActionLabel(__('pages/posts.comment_post'))
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Placeholder::make('comment_thread')
                    ->hiddenLabel()
                    ->content(fn (): HtmlString => new HtmlString($this->commentsHtml((int) $this->viewingPostId))),
                Textarea::make('body')
                    ->hiddenLabel()
                    ->placeholder(__('pages/posts.comment_placeholder'))
                    ->required()
                    ->rows(3),
                Select::make('mentions')
                    ->label(__('pages/posts.comment_mention'))
                    ->multiple()
                    ->options(fn (): array => $this->workspaceMembers())
                    ->placeholder(__('pages/posts.comment_mention_placeholder')),
                FileUpload::make('attachments')
                    ->label(__('pages/posts.comment_attachments'))
                    ->multiple()
                    ->disk('uploads')
                    ->directory('post-comments')
                    ->maxSize(25000)
                    ->maxFiles(5),
            ])
            ->action(function (array $data): void {
                $post = Post::find($this->viewingPostId);
                if ($post === null) {
                    return;
                }

                $comment = PostComment::create([
                    'post_id' => $post->id,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()?->name,
                    'body' => (string) $data['body'],
                    'attachments' => array_values($data['attachments'] ?? []),
                    'mentioned_user_ids' => array_values(array_map('intval', $data['mentions'] ?? [])),
                ]);

                app(PostCommentNotifier::class)->notifyMentioned($post, $comment);
                ActivityLogger::log('post.commented', [], $post);

                // Reopen so the thread shows the new comment.
                $this->replaceMountedAction('comments');
            });
    }

    /** Workspace members available to @-mention, id => name. */
    private function workspaceMembers(): array
    {
        $workspace = tenant();

        return $workspace instanceof Workspace
            ? $workspace->users()->orderBy('name')->pluck('name', 'users.id')->all()
            : [];
    }

    /** Rendered comment thread for a post (author, body, attachments, time). */
    private function commentsHtml(int $postId): string
    {
        $comments = PostComment::query()->where('post_id', $postId)->orderBy('created_at')->get();

        if ($comments->isEmpty()) {
            return '<div class="fp-empty">'
                .'<div class="fp-empty-title">'.e(__('pages/posts.comments_empty_title')).'</div>'
                .'<div class="fp-empty-body">'.e(__('pages/posts.comments_empty')).'</div>'
                .'</div>';
        }

        $rows = $comments->map(function (PostComment $c): string {
            $who = (string) ($c->user_name ?? __('pages/posts.activity_system'));
            $when = e($c->created_at?->diffForHumans() ?? '');
            $body = nl2br(e((string) $c->body));

            $attachments = '';
            foreach ($c->attachments ?? [] as $path) {
                $url = e(url(Storage::disk('uploads')->url((string) $path)));
                $name = e(basename((string) $path));
                $attachments .= '<a href="'.$url.'" target="_blank" rel="noopener" class="fp-file">📎 '.$name.'</a>';
            }

            return '<div class="fp-comment">'
                .'<span class="fp-avatar">'.e(mb_strtoupper(mb_substr($who, 0, 1))).'</span>'
                .'<span class="fp-comment-main">'
                .'<span class="fp-comment-head"><strong>'.e($who).'</strong> <span>'.$when.'</span></span>'
                .'<span class="fp-comment-body">'.$body.'</span>'
                .($attachments !== '' ? '<span>'.$attachments.'</span>' : '')
                .'</span>'
                .'</div>';
        })->implode('');

        return '<div class="fp-thread">'.$rows.'</div>';
    }

    /** Assign labels to the currently-viewed post (a top-level dialog, so it's
     *  not a form field inside the composer). Mounted from the post dialogs. */
    public function assignLabelsAction(): Action
    {
        return Action::make('assignLabels')
            ->modalHeading(__('pages/posts.labels_assign'))
            ->modalSubmitActionLabel(__('common.save'))
            ->modalWidth(Width::Medium)
            ->fillForm(fn (): array => ['label_ids' => Post::find($this->viewingPostId)?->label_ids ?? []])
            ->schema([
                Select::make('label_ids')
                    ->hiddenLabel()
                    ->multiple()
                    ->options(fn (): array => PostLabel::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder(__('pages/posts.labels_none'))
                    ->helperText(__('pages/posts.labels_manage_hint')),
            ])
            ->action(function (array $data): void {
                $post = Post::find($this->viewingPostId);
                if ($post === null) {
                    return;
                }

                $post->forceFill(['label_ids' => array_values(array_map('intval', $data['label_ids'] ?? []))])->save();
                Notification::make()->title(__('pages/posts.labels_saved'))->success()->send();
            });
    }

    /** Top-level label manager: create, rename, recolor and delete the
     *  workspace's post labels in one reliable (non-nested) dialog. */
    public function manageLabelsAction(): Action
    {
        return Action::make('manageLabels')
            ->label(__('pages/posts.labels_manage'))
            ->icon(Heroicon::OutlinedTag)
            ->color('gray')
            ->visible(fn (): bool => $this->isConfigured())
            ->modalHeading(__('pages/posts.labels_manage'))
            ->modalSubmitActionLabel(__('common.save'))
            ->fillForm(fn (): array => [
                'labels' => PostLabel::query()->orderBy('name')->get()
                    ->map(fn (PostLabel $l): array => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])
                    ->all(),
            ])
            ->schema([
                Repeater::make('labels')
                    ->hiddenLabel()
                    ->addActionLabel(__('pages/posts.labels_add'))
                    ->reorderable(false)
                    ->schema([
                        Hidden::make('id'),
                        TextInput::make('name')
                            ->hiddenLabel()
                            ->placeholder(__('pages/posts.label_name'))
                            ->required()
                            ->maxLength(60),
                        Select::make('color')
                            ->hiddenLabel()
                            ->options(collect(PostLabel::COLORS)->keys()->mapWithKeys(
                                fn (string $c): array => [$c => __('pages/posts.color_'.$c)],
                            )->all())
                            ->default('blue')
                            ->required(),
                    ])
                    ->columns(2)
                    ->grid(1),
            ])
            ->action(fn (array $data) => $this->syncLabels($data['labels'] ?? []));
    }

    /**
     * Reconcile the label set from the manager: update existing rows, create
     * new ones, and delete any that were removed.
     *
     * @param  array<int, array{id?: int|null, name?: string, color?: string}>  $rows
     */
    protected function syncLabels(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $attributes = ['name' => $name, 'color' => (string) ($row['color'] ?? 'blue')];
            $id = $row['id'] ?? null;

            if ($id !== null && $id !== '') {
                PostLabel::query()->whereKey($id)->update($attributes);
                $keptIds[] = (int) $id;
            } else {
                $keptIds[] = PostLabel::create($attributes)->getKey();
            }
        }

        PostLabel::query()->whereNotIn('id', $keptIds ?: [0])->delete();

        Notification::make()->title(__('pages/posts.labels_saved'))->success()->send();
    }

    /** The composer form next to a live Google-style preview of the post. */
    protected function composerSchema(bool $withFeedback = false): array
    {
        // With feedback: form | live preview | feedback panel, three columns
        // like the Planable reference. Without (new post): form | preview.
        return [
            Grid::make(['default' => 1, 'lg' => $withFeedback ? 3 : 2])
                ->schema(array_values(array_filter([
                    Group::make($this->formSchema()),
                    Group::make([
                        Placeholder::make('post_preview')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => new HtmlString($this->previewHtml($get))),
                    ])->extraAttributes(['class' => 'lg:sticky lg:top-4']),
                    $withFeedback
                        ? Group::make([
                            Placeholder::make('post_feedback')
                                ->hiddenLabel()
                                ->content(fn (): HtmlString => new HtmlString($this->feedbackPanelHtml((int) $this->viewingPostId))),
                        ])->extraAttributes(['class' => 'lg:sticky lg:top-4'])
                        : null,
                ]))),
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

    /** Comment composer state for the feedback panel in the view dialog. */
    public string $commentBody = '';

    /** @var list<int|string> */
    public array $commentMentions = [];

    /** @var array<int, TemporaryUploadedFile> */
    public $commentFiles = [];

    public function showPost(int $postId): void
    {
        $this->viewingPostId = $postId;
        $this->resetCommentComposer();

        // Drafts open in the editable composer; everything else is history and
        // gets the read-only details dialog.
        $this->mountAction(Post::find($postId)?->status === 'draft' ? 'editDraft' : 'viewPost');
    }

    private function resetCommentComposer(): void
    {
        $this->commentBody = '';
        $this->commentMentions = [];
        $this->commentFiles = [];
    }

    // ── Labels popover (inside the feedback panel) ──────────────────────────
    // Server-side state so the popover survives the Livewire re-render that
    // every toggle triggers (an Alpine-only popover would snap shut).

    public bool $labelsPopoverOpen = false;

    public ?int $editingLabelId = null;

    public string $editingLabelName = '';

    public string $editingLabelColor = 'blue';

    public string $newLabelName = '';

    public string $newLabelColor = 'blue';

    public function toggleLabelsPopover(): void
    {
        $this->labelsPopoverOpen = ! $this->labelsPopoverOpen;
        $this->editingLabelId = null;
        $this->newLabelName = '';
    }

    /** Assign/unassign a label on the open post without closing anything. */
    public function togglePostLabel(int $labelId): void
    {
        $post = Post::find($this->viewingPostId);
        if ($post === null || PostLabel::find($labelId) === null) {
            return;
        }

        $ids = array_map('intval', $post->label_ids ?? []);
        $ids = in_array($labelId, $ids, true)
            ? array_values(array_diff($ids, [$labelId]))
            : [...$ids, $labelId];

        $post->forceFill(['label_ids' => $ids])->save();
    }

    /** Create a label from the popover and assign it to the open post. */
    public function createLabelInline(): void
    {
        $name = trim($this->newLabelName);
        if ($name === '') {
            return;
        }

        $label = PostLabel::create(['name' => $name, 'color' => $this->newLabelColor]);
        $this->newLabelName = '';
        $this->togglePostLabel((int) $label->getKey());
    }

    public function startEditLabel(int $labelId): void
    {
        $label = PostLabel::find($labelId);
        if ($label === null) {
            return;
        }

        $this->editingLabelId = $labelId;
        $this->editingLabelName = $label->name;
        $this->editingLabelColor = $label->color;
    }

    public function saveEditedLabel(): void
    {
        $name = trim($this->editingLabelName);
        if ($this->editingLabelId === null || $name === '') {
            return;
        }

        PostLabel::query()->whereKey($this->editingLabelId)
            ->update(['name' => $name, 'color' => $this->editingLabelColor]);
        $this->editingLabelId = null;
    }

    public function deleteLabel(int $labelId): void
    {
        PostLabel::query()->whereKey($labelId)->delete();

        // Detach from the open post right away (other posts are filtered on
        // render, stale ids there are harmless).
        $post = Post::find($this->viewingPostId);
        if ($post !== null && in_array($labelId, array_map('intval', $post->label_ids ?? []), true)) {
            $post->forceFill(['label_ids' => array_values(array_diff(array_map('intval', $post->label_ids), [$labelId]))])->save();
        }

        $this->editingLabelId = null;
    }

    /** Post a comment from the feedback panel (right column of the view dialog). */
    public function addComment(): void
    {
        $post = Post::find($this->viewingPostId);
        $body = trim($this->commentBody);

        if ($post === null || ($body === '' && $this->commentFiles === [])) {
            return;
        }

        $paths = [];
        foreach ($this->commentFiles as $file) {
            try {
                $paths[] = $file->store('post-comments', 'uploads');
            } catch (\Throwable $e) {
                Log::warning('Comment attachment failed', ['error' => $e->getMessage()]);
            }
        }

        // Only real workspace members can be mentioned.
        $members = array_map('intval', array_keys($this->workspaceMembers()));
        $mentions = array_values(array_intersect(array_map('intval', $this->commentMentions), $members));

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'body' => $body,
            'attachments' => $paths,
            'mentioned_user_ids' => $mentions,
        ]);

        app(PostCommentNotifier::class)->notifyMentioned($post, $comment);
        ActivityLogger::log('post.commented', [], $post);

        $this->resetCommentComposer();
    }

    /** Details modal for a calendar card. */
    public function viewPostAction(): Action
    {
        return Action::make('viewPost')
            ->modalHeading(fn (): string => __('pages/posts.type_'.(Post::find($this->viewingPostId)?->type ?? 'update')))
            // Two columns: the Google-style preview and a Planable-style
            // feedback panel (labels, comments, activity) on the right.
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('pages/posts.close'))
            ->schema(fn (): array => [
                Placeholder::make('post_details')
                    ->hiddenLabel()
                    ->content(fn (): HtmlString => new HtmlString(
                        '<div style="display:grid; grid-template-columns:minmax(0,26rem) minmax(0,1fr); gap:1.4rem; align-items:start;" class="pv-grid">'
                        .'<div>'.$this->postDetailsHtml((int) $this->viewingPostId).'</div>'
                        .$this->feedbackPanelHtml((int) $this->viewingPostId)
                        .'</div>'
                        .'<style>@media (max-width: 860px) { .pv-grid { grid-template-columns: 1fr !important; } }</style>'
                    )),
            ])
            ->extraModalFooterActions(fn (): array => array_values(array_filter([
                // Scheduled/failed posts we sent can be pulled back to a draft
                // to edit and re-send (cancels the Zernio-side schedule first).
                in_array(Post::find($this->viewingPostId)?->status, ['scheduled', 'failed'], true)
                && Post::find($this->viewingPostId)?->origin !== 'imported'
                    ? Action::make('editPost')
                        ->label(__('pages/posts.edit'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->action(fn () => $this->revertToDraftAndEdit())
                    : null,
                Action::make('duplicateDraft')
                    ->label(__('pages/posts.duplicate_draft'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn () => $this->duplicateAsDraft((int) $this->viewingPostId))
                    ->cancelParentActions(),
                // Imported (Google-owned) posts can't be deleted from here: they
                // just re-import on the next sync. Everything we sent can.
                Post::find($this->viewingPostId)?->origin === 'imported'
                    ? null
                    : Action::make('delete')
                        ->label(__('pages/posts.delete'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->action(fn () => $this->replaceMountedAction('deletePost')),
            ])));
    }

    /** Pull a scheduled/failed post back to a draft (cancelling any Zernio-side
     *  schedule) and reopen it in the composer for editing. */
    public function revertToDraftAndEdit(): void
    {
        $post = Post::find($this->viewingPostId);
        if ($post === null) {
            return;
        }

        $fromStatus = $post->status;

        if ($fromStatus === 'scheduled') {
            $this->cancelScheduledOnZernio($post);
        }

        $post->forceFill(['status' => 'draft', 'external_ids' => []])->save();
        ActivityLogger::log('post.reverted', ['from' => $fromStatus], $post);

        $this->replaceMountedAction('editDraft');
    }

    /** Confirm-and-delete a post (its own top-level modal, mounted from the
     *  view/edit dialogs so the confirmation isn't a flaky nested modal). */
    public function deletePostAction(): Action
    {
        return Action::make('deletePost')
            ->modalHeading(__('pages/posts.delete'))
            ->modalDescription(__('pages/posts.delete_desc'))
            ->modalSubmitActionLabel(__('pages/posts.delete'))
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalIconColor('danger')
            ->action(function (): void {
                $post = Post::find($this->viewingPostId);
                if ($post === null) {
                    return;
                }

                // A still-scheduled post lives on Zernio too; cancel it there
                // first so it doesn't publish after we drop our row. Best-effort.
                if ($post->status === 'scheduled') {
                    $this->cancelScheduledOnZernio($post);
                }

                ActivityLogger::log('post.deleted', ['type' => $post->type, 'status' => $post->status], $post);
                $post->delete();
                Notification::make()->title(__('pages/posts.deleted'))->success()->send();
            });
    }

    /** Best-effort cancel of a post's Zernio-side scheduled copies. */
    private function cancelScheduledOnZernio(Post $post): void
    {
        foreach ($post->external_ids ?? [] as $externalId) {
            if (! is_string($externalId) || $externalId === '') {
                continue;
            }

            try {
                app(ZernioRestClient::class)->deletePost($externalId);
            } catch (\Throwable $e) {
                Log::warning('Cancel scheduled post on Zernio failed', ['post' => $post->id, 'external_id' => $externalId, 'error' => $e->getMessage()]);
            }
        }
    }

    /** Copy any post (including an imported Google one) into a fresh draft. */
    public function duplicateAsDraft(int $postId): void
    {
        $post = Post::find($postId);
        if ($post === null) {
            return;
        }

        $copy = Post::create([
            'type' => in_array($post->type, ['update', 'offer', 'event', 'photo'], true) ? $post->type : 'update',
            'caption' => $post->caption,
            'title' => $post->title,
            'image_url' => $post->image_url,
            'video_url' => $post->video_url,
            'cta_type' => $post->cta_type,
            'cta_url' => $post->cta_url,
            'photo_category' => $post->photo_category,
            'starts_at' => $post->starts_at,
            'ends_at' => $post->ends_at,
            'voucher_code' => $post->voucher_code,
            'redeem_url' => $post->redeem_url,
            'terms_url' => $post->terms_url,
            'location_ids' => $post->location_ids ?? [],
            'label_ids' => $post->label_ids ?? [],
            // source_ids is a NOT NULL json column; a fresh draft hasn't been
            // sent anywhere yet, so it starts empty.
            'source_ids' => [],
            'status' => 'draft',
            'origin' => 'app',
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()?->name,
        ]);

        ActivityLogger::log('post.duplicated', ['from' => $post->id], $copy);
        Notification::make()->title(__('pages/posts.duplicated_draft'))->success()->send();
    }

    /** Drafts reopen in the full composer: publish, keep as draft, or discard. */
    public function editDraftAction(): Action
    {
        return Action::make('editDraft')
            ->modalHeading(__('pages/posts.draft_heading'))
            ->modalSubmitActionLabel(__('pages/posts.submit'))
            // Wider than the create dialog: three columns (form | preview |
            // feedback panel), the same collaboration surface as the view
            // dialog — no separate footer dialogs.
            ->modalWidth(Width::SevenExtraLarge)
            ->schema($this->composerSchema(withFeedback: true))
            ->fillForm(fn (): array => $this->draftFormState())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('saveDraft', arguments: ['draft' => true])
                    ->label(__('pages/posts.save_draft'))
                    ->color('gray'),
                Action::make('deleteDraft')
                    ->label(__('pages/posts.draft_delete'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->action(fn () => $this->replaceMountedAction('deletePost')),
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
            'media' => $this->imagePathFromUrl($post->video_url ?: $post->image_url),
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

    /** Whether a stored media path (or URL) points at a video by extension. */
    private function isVideoPath(?string $path): bool
    {
        return (bool) preg_match('/\.(mp4|mov|m4v|webm)$/i', (string) $path);
    }

    /** Public URL for an uploaded media path, or null when nothing is set. */
    private function mediaUrl(?string $path): ?string
    {
        return filled($path) ? url(Storage::disk('uploads')->url($path)) : null;
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
            'videoUrl' => filled($post->video_url) ? $post->video_url : null,
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

    /**
     * The Planable-style right column of the view dialog: assigned labels, a
     * Comments / Activity tab switch, the comment thread and a live composer
     * (textarea + mentions + attachments) wired straight to this page.
     */
    private function feedbackPanelHtml(int $postId, bool $bordered = true): string
    {
        $post = Post::find($postId);
        if ($post === null) {
            return '';
        }

        // Assigned labels as chips + a manage shortcut.
        $chips = PostLabel::query()->whereIn('id', $post->label_ids ?? [])->get()
            ->map(function (PostLabel $label): string {
                [$bg, $accent] = PostLabel::COLORS[$label->color] ?? PostLabel::COLORS['blue'];

                return '<span class="fp-chip" style="background:'.$bg.'; color:'.$accent.';">'.e($label->name).'</span>';
            })->implode('');

        // Members as toggleable mention pills (checkbox-backed, no roundtrip
        // until the comment is posted).
        $selfId = (int) auth()->id();
        $mentionPills = '';
        foreach ($this->workspaceMembers() as $id => $name) {
            if ((int) $id === $selfId) {
                continue;
            }
            $mentionPills .= '<label class="fp-pill"><input type="checkbox" wire:model="commentMentions" value="'.e((string) $id).'"><span>@'.e((string) $name).'</span></label>';
        }

        $fileChips = '';
        foreach ($this->commentFiles as $file) {
            $fileChips .= '<span class="fp-file">📎 '.e($file->getClientOriginalName()).'</span>';
        }

        $count = PostComment::query()->where('post_id', $postId)->count();

        return '<div x-data="{ tab: \'comments\' }" class="fp-panel '.($bordered ? 'fp-bordered' : 'fp-stacked').'">'
            .$this->feedbackPanelCss()
            // Labels row + Planable-style popover (assign, create, edit, delete
            // in place — nothing closes).
            .'<div class="fp-labels">'
            .($chips !== '' ? $chips : '<span class="fp-muted">'.e(__('pages/posts.labels_none')).'</span>')
            .'<span style="position:relative;">'
            .'<button type="button" class="fp-link" wire:click="toggleLabelsPopover">'.e(__('pages/posts.labels_edit')).'</button>'
            .($this->labelsPopoverOpen ? $this->labelsPopoverHtml($post) : '')
            .'</span>'
            .'</div>'
            // Tabs
            .'<div class="fp-tabs">'
            .'<button type="button" @click="tab = \'comments\'" :class="tab === \'comments\' ? \'active\' : \'\'">'.e(__('pages/posts.comments')).($count > 0 ? ' <span class="fp-count">'.$count.'</span>' : '').'</button>'
            .'<button type="button" @click="tab = \'activity\'" :class="tab === \'activity\' ? \'active\' : \'\'">'.e(__('pages/posts.activity_title')).'</button>'
            .'</div>'
            // Comments tab: thread + composer card
            .'<div x-show="tab === \'comments\'">'
            .$this->commentsHtml($postId)
            .'<div class="fp-composer">'
            .'<textarea wire:model="commentBody" rows="3" placeholder="'.e(__('pages/posts.comment_placeholder')).'"></textarea>'
            .($mentionPills !== '' ? '<div class="fp-pills">'.$mentionPills.'</div>' : '')
            .($fileChips !== '' ? '<div class="fp-files">'.$fileChips.' <button type="button" class="fp-link" wire:click="$set(\'commentFiles\', [])">✕</button></div>' : '')
            .'<div class="fp-composer-bar">'
            .'<label class="fp-attach" title="'.e(__('pages/posts.comment_attachments')).'">'
            .'<input type="file" wire:model="commentFiles" multiple>'
            .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>'
            .'<span wire:loading wire:target="commentFiles" class="fp-uploading">…</span>'
            .'</label>'
            .'<button type="button" class="fp-send" wire:click="addComment" wire:loading.attr="disabled" wire:target="addComment, commentFiles">'
            .'<span wire:loading.remove wire:target="addComment">'.e(__('pages/posts.comment_post')).'</span>'
            .'<span wire:loading wire:target="addComment">…</span>'
            .'</button>'
            .'</div>'
            .'</div>'
            .'</div>'
            // Activity tab
            .'<div x-show="tab === \'activity\'" x-cloak>'.($this->activityFeedHtml($postId) ?: '<div class="fp-muted" style="padding:.4rem 0;">—</div>').'</div>'
            .'</div>';
    }

    /** The "Add labels" popover: check to assign, pencil to edit, trash to
     *  delete, plus inline create with a color dot picker. */
    private function labelsPopoverHtml(Post $post): string
    {
        $assigned = array_map('intval', $post->label_ids ?? []);

        $rows = PostLabel::query()->orderBy('name')->get()->map(function (PostLabel $label) use ($assigned): string {
            [$bg, $accent] = PostLabel::COLORS[$label->color] ?? PostLabel::COLORS['blue'];
            $id = (int) $label->getKey();

            // Row flips to an inline editor while being edited.
            if ($this->editingLabelId === $id) {
                return '<div class="fp-pop-row fp-pop-edit">'
                    .'<input type="text" wire:model="editingLabelName" wire:keydown.enter="saveEditedLabel" />'
                    .$this->colorDotsHtml('editingLabelColor', $this->editingLabelColor)
                    .'<span class="fp-pop-row-actions">'
                    .'<button type="button" class="fp-link" wire:click="saveEditedLabel">✓</button>'
                    .'<button type="button" class="fp-link fp-danger" wire:click="deleteLabel('.$id.')">🗑</button>'
                    .'</span>'
                    .'</div>';
            }

            return '<div class="fp-pop-row">'
                .'<label class="fp-pop-check">'
                .'<input type="checkbox" wire:click="togglePostLabel('.$id.')" '.(in_array($id, $assigned, true) ? 'checked' : '').'>'
                .'<span class="fp-chip" style="background:'.$bg.'; color:'.$accent.';">'.e($label->name).'</span>'
                .'</label>'
                .'<button type="button" class="fp-pop-gear" wire:click="startEditLabel('.$id.')" title="'.e(__('pages/posts.labels_edit')).'">'
                .'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l12.682-12.682Z"/></svg>'
                .'</button>'
                .'</div>';
        })->implode('');

        return '<div class="fp-pop" x-data @click.outside="$wire.set(\'labelsPopoverOpen\', false)">'
            .'<div class="fp-pop-title">'.e(__('pages/posts.labels_assign_title')).'</div>'
            .($rows !== '' ? $rows : '<div class="fp-muted" style="padding:.3rem 0 .5rem;">'.e(__('pages/posts.labels_none')).'</div>')
            .'<div class="fp-pop-create">'
            .'<input type="text" wire:model="newLabelName" wire:keydown.enter="createLabelInline" placeholder="'.e(__('pages/posts.labels_create_placeholder')).'" />'
            .$this->colorDotsHtml('newLabelColor', $this->newLabelColor)
            .'<button type="button" class="fp-send fp-pop-add" wire:click="createLabelInline">+</button>'
            .'</div>'
            .'</div>';
    }

    /** A row of clickable color dots bound to a Livewire property. */
    private function colorDotsHtml(string $property, string $selected): string
    {
        $dots = '';
        foreach (PostLabel::COLORS as $key => [$bg, $accent]) {
            $dots .= '<button type="button" class="fp-dot'.($key === $selected ? ' active' : '').'" style="background:'.$accent.';" wire:click="$set(\''.$property.'\', \''.$key.'\')" title="'.e(__('pages/posts.color_'.$key)).'"></button>';
        }

        return '<span class="fp-dots">'.$dots.'</span>';
    }

    /** Scoped styles for the feedback panel (light + dark). Emitted once per panel. */
    private function feedbackPanelCss(): string
    {
        return <<<'HTML'
            <style>
                .fp-panel { font-size: .85rem; }
                .fp-bordered { border-left: 1px solid #eceef2; padding-left: 1.4rem; min-height: 20rem; }
                .fp-stacked { border-top: 1px solid #eceef2; padding-top: 1rem; }
                .dark .fp-bordered { border-color: rgb(255 255 255 / .08); }
                .dark .fp-stacked { border-color: rgb(255 255 255 / .08); }
                .fp-muted { color: #9ca3af; font-size: .78rem; }
                .fp-link { background: none; border: none; cursor: pointer; color: #2d19ec; font-size: .74rem; font-weight: 600; padding: .1rem .25rem; }
                .dark .fp-link { color: #a5b4fc; }
                .fp-labels { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; margin-bottom: .8rem; }
                .fp-chip { font-size: .68rem; font-weight: 700; letter-spacing: .02em; padding: .18rem .55rem; border-radius: 999px; }
                .fp-tabs { display: flex; gap: .2rem; border-bottom: 1px solid #eceef2; margin-bottom: .7rem; }
                .dark .fp-tabs { border-color: rgb(255 255 255 / .08); }
                .fp-tabs button { background: none; border: none; cursor: pointer; padding: .4rem .65rem; font-size: .84rem; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -1px; }
                .fp-tabs button.active { color: #2d19ec; border-color: #2d19ec; }
                .dark .fp-tabs button { color: #a1a1aa; }
                .dark .fp-tabs button.active { color: #a5b4fc; border-color: #a5b4fc; }
                .fp-count { font-size: .66rem; background: #eef2ff; color: #2d19ec; border-radius: 999px; padding: .08rem .4rem; }
                .dark .fp-count { background: rgb(99 102 241 / .2); color: #a5b4fc; }
                .fp-empty { text-align: center; padding: 1.4rem .5rem 1.6rem; }
                .fp-empty-title { font-weight: 600; font-size: .9rem; margin-bottom: .2rem; }
                .fp-empty-body { color: #9ca3af; font-size: .8rem; }
                .fp-thread { max-height: 16rem; overflow: auto; margin-bottom: .6rem; }
                .fp-comment { display: flex; gap: .55rem; padding: .55rem 0; }
                .fp-comment + .fp-comment { border-top: 1px solid #f1f2f4; }
                .dark .fp-comment + .fp-comment { border-color: rgb(255 255 255 / .06); }
                .fp-avatar { flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; background: #eef2ff; color: #2d19ec; font-size: .8rem; font-weight: 700; display: grid; place-items: center; }
                .dark .fp-avatar { background: rgb(99 102 241 / .22); color: #a5b4fc; }
                .fp-comment-main { min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
                .fp-comment-head { font-size: .8rem; }
                .fp-comment-head span { color: #9ca3af; font-size: .72rem; }
                .fp-comment-body { font-size: .85rem; color: #374151; overflow-wrap: anywhere; }
                .dark .fp-comment-body { color: #d4d4d8; }
                .fp-file { display: inline-flex; align-items: center; gap: .25rem; margin: .25rem .25rem 0 0; padding: .15rem .5rem; border: 1px solid #e5e7eb; border-radius: 999px; font-size: .72rem; color: #2d19ec; text-decoration: none; }
                .dark .fp-file { border-color: rgb(255 255 255 / .12); color: #a5b4fc; }
                .fp-composer { border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; overflow: hidden; }
                .dark .fp-composer { border-color: rgb(255 255 255 / .12); background: rgb(255 255 255 / .04); }
                .fp-composer:focus-within { border-color: #2d19ec66; }
                .fp-composer textarea { display: block; width: 100%; border: none; outline: none; background: transparent; resize: vertical; padding: .6rem .75rem .3rem; font-size: .85rem; color: inherit; }
                .fp-pills { display: flex; flex-wrap: wrap; gap: .3rem; padding: .25rem .6rem .1rem; }
                .fp-pill input { position: absolute; opacity: 0; pointer-events: none; }
                .fp-pill span { display: inline-block; font-size: .7rem; font-weight: 600; padding: .18rem .55rem; border-radius: 999px; border: 1px solid #e5e7eb; color: #6b7280; cursor: pointer; transition: all .12s ease; }
                .fp-pill:hover span { border-color: #2d19ec66; color: #2d19ec; }
                .fp-pill:has(input:checked) span { background: #eef2ff; border-color: #2d19ec; color: #2d19ec; }
                .dark .fp-pill span { border-color: rgb(255 255 255 / .14); color: #a1a1aa; }
                .dark .fp-pill:has(input:checked) span { background: rgb(99 102 241 / .2); border-color: #a5b4fc; color: #a5b4fc; }
                .fp-files { padding: .2rem .6rem 0; }
                .fp-composer-bar { display: flex; align-items: center; justify-content: space-between; padding: .4rem .5rem .5rem .6rem; }
                .fp-attach { display: inline-flex; align-items: center; gap: .3rem; cursor: pointer; color: #9ca3af; padding: .3rem; border-radius: .4rem; }
                .fp-attach:hover { color: #2d19ec; background: #eef2ff; }
                .dark .fp-attach:hover { color: #a5b4fc; background: rgb(99 102 241 / .15); }
                .fp-attach input { display: none; }
                .fp-attach svg { width: 1.1rem; height: 1.1rem; }
                .fp-uploading { font-size: .75rem; }
                .fp-send { background: #2d19ec; color: #fff; font-size: .8rem; font-weight: 600; padding: .4rem .95rem; border: none; border-radius: 999px; cursor: pointer; }
                .fp-send:hover { background: #2413c9; }
                .fp-send[disabled] { opacity: .6; }
                /* Labels popover */
                .fp-pop { position: absolute; top: 1.6rem; left: 0; z-index: 30; width: 17rem; background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; box-shadow: 0 12px 32px -8px rgb(0 0 0 / .18); padding: .7rem .8rem .8rem; }
                .dark .fp-pop { background: #1b1b21; border-color: rgb(255 255 255 / .12); box-shadow: 0 12px 32px -8px rgb(0 0 0 / .6); }
                .fp-pop-title { font-weight: 700; font-size: .82rem; margin-bottom: .45rem; }
                .fp-pop-row { display: flex; align-items: center; justify-content: space-between; gap: .4rem; padding: .22rem 0; }
                .fp-pop-check { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; min-width: 0; }
                .fp-pop-check input { width: .95rem; height: .95rem; accent-color: #2d19ec; cursor: pointer; }
                .fp-pop-gear { background: none; border: none; cursor: pointer; color: #c2c5cc; padding: .15rem; border-radius: .3rem; flex: none; }
                .fp-pop-gear:hover { color: #2d19ec; background: #eef2ff; }
                .dark .fp-pop-gear:hover { color: #a5b4fc; background: rgb(99 102 241 / .15); }
                .fp-pop-gear svg { width: .85rem; height: .85rem; }
                .fp-pop-edit { flex-wrap: wrap; }
                .fp-pop-edit input[type="text"], .fp-pop-create input[type="text"] { flex: 1 1 6rem; min-width: 0; border: 1px solid #e5e7eb; border-radius: .45rem; padding: .3rem .5rem; font-size: .78rem; background: transparent; color: inherit; }
                .dark .fp-pop-edit input[type="text"], .dark .fp-pop-create input[type="text"] { border-color: rgb(255 255 255 / .14); }
                .fp-pop-row-actions { display: inline-flex; gap: .1rem; flex: none; }
                .fp-danger { color: #dc2626; }
                .fp-pop-create { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; margin-top: .5rem; padding-top: .6rem; border-top: 1px solid #f1f2f4; }
                .dark .fp-pop-create { border-color: rgb(255 255 255 / .08); }
                .fp-pop-add { padding: .25rem .6rem; flex: none; }
                .fp-dots { display: inline-flex; gap: .22rem; flex: none; }
                .fp-dot { width: .85rem; height: .85rem; border-radius: 999px; border: 2px solid transparent; cursor: pointer; padding: 0; }
                .fp-dot.active { border-color: #111; transform: scale(1.15); }
                .dark .fp-dot.active { border-color: #fff; }
            </style>
            HTML;
    }

    /** Compact "who did what, when" feed for one post, from the activity log. */
    private function activityFeedHtml(int $postId): string
    {
        $entries = ActivityEntry::query()
            ->where('subject_type', 'Post')
            ->where('subject_id', $postId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        if ($entries->isEmpty()) {
            return '';
        }

        $rows = $entries->map(function (ActivityEntry $entry): string {
            $key = 'pages/posts.activity_'.Str::after($entry->action, 'post.');
            $label = __($key);
            if ($label === $key) {
                $label = (string) $entry->action; // graceful fallback for an unmapped action
            }

            $who = e((string) ($entry->user_name ?? __('pages/posts.activity_system')));
            $when = e($entry->created_at?->diffForHumans() ?? '');

            return '<li style="display:flex; gap:.55rem; align-items:flex-start; padding:.35rem 0;">'
                .'<span style="flex:none; margin-top:.4rem; width:.4rem; height:.4rem; border-radius:999px; background:#c7c9d1;"></span>'
                .'<span style="min-width:0;"><span style="font-weight:600;">'.$who.'</span> '.e($label)
                .'<span style="display:block; font-size:.72rem; color:#9ca3af;">'.$when.'</span></span>'
                .'</li>';
        })->implode('');

        return '<ul style="list-style:none; margin:0; padding:0;">'.$rows.'</ul>';
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
    /** The signed-in user's timezone, used for every date/time field + label. */
    private function userTimezone(): string
    {
        return auth()->user()?->timezone ?: (config('app.timezone') ?: 'UTC');
    }

    protected function formSchema(): array
    {
        $isOfferOrEvent = fn (Get $get): bool => in_array($get('type'), ['offer', 'event'], true);
        $timezone = $this->userTimezone();

        return [
            ToggleButtons::make('type')
                ->label(__('pages/posts.field_type'))
                // Zernio's native API models a photo post as a STANDARD update
                // with an image, so only the three real GBP topic types remain.
                ->options(collect(['update', 'offer', 'event'])->mapWithKeys(
                    fn (string $t): array => [$t => __('pages/posts.type_'.$t)],
                )->all())
                ->icons([
                    'update' => Heroicon::OutlinedMegaphone,
                    'offer' => Heroicon::OutlinedTag,
                    'event' => Heroicon::OutlinedCalendarDays,
                ])
                // Icon-on-top cards in a row (styled in posts.blade.php).
                ->inline()
                ->extraAttributes(['class' => 'post-type-picker'])
                ->default('update')
                ->required()
                ->live(),

            Select::make('locations')
                ->label(__('pages/posts.field_locations'))
                ->multiple()
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): array => Location::query()->pluck('id')->all())
                ->required(),

            // Offer/Event carry a headline: keep it above the body text, with a
            // counter + hard 58-char cap (Google truncates longer titles).
            TextInput::make('title')
                ->label(__('pages/posts.field_title'))
                ->maxLength(58)
                ->extraInputAttributes(['maxlength' => 58])
                ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 58')
                ->hintColor(fn (?string $state): string => mb_strlen((string) $state) >= 58 ? 'danger' : 'gray')
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent)
                ->live(debounce: 300),

            Textarea::make('caption')
                ->label(__('pages/posts.field_caption'))
                ->rows(4)
                ->maxLength(1500)
                // Hard stop in the browser + a live counter in the label row.
                ->extraInputAttributes(['maxlength' => 1500])
                ->hint(fn (?string $state): string => mb_strlen((string) $state).' / 1500')
                ->hintColor(fn (?string $state): string => mb_strlen((string) $state) >= 1500 ? 'danger' : 'gray')
                ->required()
                ->live(debounce: 300),

            // One media slot: an image OR a video (a Google post carries a
            // single media item). The stored file's extension decides which of
            // image_url / video_url it becomes on save.
            FileUpload::make('media')
                ->label(__('pages/posts.field_media'))
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime'])
                // No client-side image resize here: the resize plugin is
                // image-only and, on a mixed image/video field, hangs FilePond
                // on "waiting for size". The 25 MB cap keeps uploads bounded.
                // Don't fetch stored-file metadata on rehydration either — for
                // a draft with a large video that re-download left the field
                // stuck on "Loading / waiting for size".
                ->fetchFileInformation(false)
                ->disk('uploads')
                ->directory('posts')
                ->maxSize(25000)
                ->live()
                ->helperText(__('pages/posts.field_media_helper')),

            DateTimePicker::make('starts_at')
                ->label(__('pages/posts.field_starts'))
                ->seconds(false)
                // JS calendar/time picker (not the raw browser input), in the
                // user's own timezone.
                ->native(false)
                ->prefixIcon(Heroicon::OutlinedCalendar)
                ->timezone($timezone)
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent)
                ->live(),

            DateTimePicker::make('ends_at')
                ->label(__('pages/posts.field_ends'))
                ->seconds(false)
                ->native(false)
                ->prefixIcon(Heroicon::OutlinedCalendar)
                ->timezone($timezone)
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
                ->placeholder('https://example.com')
                ->required(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call')
                ->visible(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call'
                    && in_array($get('type'), ['update', 'event'], true)),

            DateTimePicker::make('scheduled_at')
                ->label(__('pages/posts.field_schedule'))
                ->seconds(false)
                ->native(false)
                ->prefixIcon(Heroicon::OutlinedCalendar)
                ->timezone($timezone)
                ->minDate(now())
                ->default(fn (): ?string => $this->pullPrefillDate())
                ->helperText(__('pages/posts.field_schedule_helper', ['tz' => $timezone])),
        ];
    }

    /** Live preview of the composed post, styled like the card on Google Maps. */
    /**
     * The Google-Maps-style post card from normalized data. Shared by the
     * read-only post view (postDetailsHtml) so it matches the composer preview.
     *
     * @param  array{name:string,date:string,logoUrl:?string,imageUrl:?string,videoUrl?:?string,title:?string,dates:?string,caption:?string,captionPlaceholder?:bool,voucher:?string,cta:?string}  $d
     */
    private function googlePreviewCard(array $d): string
    {
        $name = (string) ($d['name'] ?? '');
        $logoUrl = $d['logoUrl'] ?? null;
        $imageUrl = $d['imageUrl'] ?? null;
        $videoUrl = $d['videoUrl'] ?? null;

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

        if ($videoUrl !== null) {
            $html .= '<video src="'.e($videoUrl).'" controls playsinline style="display:block; width:100%; aspect-ratio:2/1; object-fit:cover; background:#000;"></video>';
        } elseif ($imageUrl !== null) {
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
                .'<span style="color:#0d766e; font-weight:600; font-size:.9rem; cursor:default; user-select:none;" title="'.e(__('pages/posts.preview_cta_hint')).'">'.e(__('pages/posts.cta_'.$cta)).'</span>'
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

        // One media slot: figure out whether it's an image or a video, and its
        // URL, from either a fresh upload or a re-hydrated draft path.
        $media = $get('media');
        $media = is_array($media) ? collect($media)->first() : $media;
        $imageUrl = null;
        $videoUrl = null;
        if ($media instanceof TemporaryUploadedFile) {
            $url = $media->temporaryUrl();
            str_starts_with((string) $media->getMimeType(), 'video/') ? $videoUrl = $url : $imageUrl = $url;
        } elseif (is_string($media) && filled($media)) {
            $url = url(Storage::disk('uploads')->url($media));
            $this->isVideoPath($media) ? $videoUrl = $url : $imageUrl = $url;
        }

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

        if ($videoUrl !== null) {
            $html .= '<video src="'.e($videoUrl).'" controls playsinline style="display:block; width:100%; aspect-ratio:2/1; object-fit:cover; background:#000;"></video>';
        } elseif ($imageUrl !== null) {
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
                .'<span style="color:#0d766e; font-weight:600; font-size:.9rem; cursor:default; user-select:none;" title="'.e(__('pages/posts.preview_cta_hint')).'">'.e(__('pages/posts.cta_'.$cta)).'</span>'
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
            'image_url' => $this->isVideoPath($data['media'] ?? null) ? null : $this->mediaUrl($data['media'] ?? null),
            'video_url' => $this->isVideoPath($data['media'] ?? null) ? $this->mediaUrl($data['media'] ?? null) : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'voucher_code' => $data['voucher_code'] ?? null,
            'redeem_url' => $data['redeem_url'] ?? null,
            'terms_url' => $data['terms_url'] ?? null,
            'location_ids' => $locations->pluck('id')->all(),
            'source_ids' => $locations->pluck('external_id')->all(),
            // label_ids is managed separately (per-post "Labels" action), so it
            // is intentionally NOT set here — updates preserve existing labels.
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
            ActivityLogger::log($existing !== null ? 'post.draft_updated' : 'post.draft_created', ['type' => $post->type], $post);
            Notification::make()->title(__('pages/posts.draft_saved'))->success()->send();

            return;
        }

        app(PostPublisher::class)->publish($post, $locations);
        $post->refresh();

        if ($post->status === 'failed') {
            ActivityLogger::log('post.publish_failed', ['error' => Str::limit((string) $post->error, 120)], $post);
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
