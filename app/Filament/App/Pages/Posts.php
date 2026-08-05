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
use App\Models\PostShare;
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
use Filament\Actions\ActionGroup;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
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
                    ->maxSize(102400)
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

        // Threaded: top-level comments with their replies nested underneath.
        $byParent = $comments->groupBy(fn (PostComment $c) => $c->parent_id ?? 0);

        $rows = ($byParent->get(0) ?? collect())->map(function (PostComment $c) use ($byParent): string {
            $replies = ($byParent->get($c->id) ?? collect())
                ->map(fn (PostComment $r): string => $this->commentRowHtml($r, isReply: true))
                ->implode('');

            return $this->commentRowHtml($c).($replies !== '' ? '<div class="fp-replies">'.$replies.'</div>' : '');
        })->implode('');

        return '<div class="fp-thread">'.$rows.'</div>';
    }

    /** One comment row: header with hover actions, body (or inline editor),
     *  attachments and grouped emoji reactions. */
    private function commentRowHtml(PostComment $c, bool $isReply = false): string
    {
        $who = (string) ($c->user_name ?? __('pages/posts.activity_system'));
        $when = e($c->created_at?->diffForHumans() ?? '');
        $isMine = (int) $c->user_id === (int) auth()->id();

        $attachments = '';
        foreach ($c->attachments ?? [] as $path) {
            $url = e(url(Storage::disk('uploads')->url((string) $path)));
            $name = e(basename((string) $path));
            $attachments .= '<a href="'.$url.'" target="_blank" rel="noopener" class="fp-file">'.$this->icon('o-paper-clip').' '.$name.'</a>';
        }

        // Reaction picker (hover) + grouped chips with reactor names on hover.
        $picker = '';
        foreach (PostComment::REACTION_EMOJI as $emoji) {
            $picker .= '<button type="button" wire:click="toggleReaction('.$c->id.', \''.$emoji.'\')" @click="react = false">'.$emoji.'</button>';
        }

        $chips = '';
        foreach ($c->groupedReactions((int) auth()->id()) as $emoji => $group) {
            $chips .= '<button type="button" class="fp-reaction'.($group['mine'] ? ' mine' : '').'"'
                .' wire:click="toggleReaction('.$c->id.', \''.$emoji.'\')"'
                .' title="'.e(implode(', ', $group['names'])).'">'
                .$emoji.' <span>'.count($group['names']).'</span></button>';
        }

        // Hover actions: react, reply, and the "…" menu (edit/delete, own only).
        $actions = '<span class="fp-c-actions" x-data="{ menu: false, react: false }">'
            .'<span style="position:relative;">'
            .'<button type="button" class="fp-c-btn" @click="react = ! react; menu = false" title="'.e(__('pages/posts.comment_react')).'">'
            .$this->icon('o-face-smile')
            .'</button>'
            .'<span class="fp-react-pop" x-show="react" x-cloak @click.outside="react = false">'.$picker.'</span>'
            .'</span>'
            .'<button type="button" class="fp-c-btn" wire:click="startReply('.$c->id.')" title="'.e(__('pages/posts.comment_reply')).'">'
            .$this->icon('o-arrow-uturn-left')
            .'</button>'
            .($isMine
                ? '<span style="position:relative;">'
                .'<button type="button" class="fp-c-btn" @click="menu = ! menu; react = false">'
                .$this->icon('o-ellipsis-horizontal')
                .'</button>'
                .'<span class="fp-menu" x-show="menu" x-cloak @click.outside="menu = false">'
                .'<button type="button" wire:click="startEditComment('.$c->id.')" @click="menu = false">'.$this->icon('o-pencil-square').' '.e(__('pages/posts.comment_edit')).'</button>'
                .'<button type="button" class="fp-danger" wire:click="deleteComment('.$c->id.')" wire:confirm="'.e(__('pages/posts.comment_delete_confirm')).'" @click="menu = false">'.$this->icon('o-trash').' '.e(__('pages/posts.comment_delete')).'</button>'
                .'</span>'
                .'</span>'
                : '')
            .'</span>';

        // Body, or the inline editor while this comment is being edited.
        $body = $this->editingCommentId === $c->id
            ? '<span class="fp-c-edit">'
                .'<textarea wire:model="editingCommentBody" rows="2"></textarea>'
                .'<span class="fp-c-edit-bar">'
                .'<button type="button" class="fp-link" wire:click="cancelEditComment">'.e(__('pages/posts.comment_cancel')).'</button>'
                .'<button type="button" class="fp-send" wire:click="saveEditedComment">'.e(__('pages/posts.comment_save')).'</button>'
                .'</span>'
                .'</span>'
            : '<span class="fp-comment-body">'.nl2br(e((string) $c->body))
                .($c->edited_at !== null ? ' <span class="fp-edited">('.e(__('pages/posts.comment_edited')).')</span>' : '')
                .'</span>';

        return '<div class="fp-comment'.($isReply ? ' fp-reply' : '').'" wire:key="comment-'.$c->id.'">'
            .$this->avatarHtml($c->user_id !== null ? (int) $c->user_id : null, $who)
            .'<span class="fp-comment-main">'
            .'<span class="fp-comment-head"><strong>'.e($who).'</strong> <span>'.$when.'</span>'.$actions.'</span>'
            .$body
            .($attachments !== '' ? '<span>'.$attachments.'</span>' : '')
            .($chips !== '' ? '<span class="fp-reactions">'.$chips.'</span>' : '')
            .'</span>'
            .'</div>';
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
        // Drop the legacy "untagged" sentinel: the filter no longer offers it,
        // so a stale session must not keep untagged notes invisible.
        $this->hiddenNoteTags = array_values(array_filter(
            (array) session('posts_hidden_note_tags', []),
            fn ($tag): bool => is_string($tag) && $tag !== self::UNTAGGED,
        ));
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
            ->tap(fn (Builder $q) => $this->applyLocationFilter($q))->tap(fn (Builder $q) => $this->applyPostFilters($q))
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

    // ── Always-visible filter bar (type / status / label / author) ─────────
    // Empty selection = show everything.

    /** @var list<string> */
    public array $filterTypes = [];

    /** @var list<string> */
    public array $filterStatuses = [];

    /** @var list<int|string> */
    public array $filterLabels = [];

    /** @var list<string> */
    public array $filterAuthors = [];

    /** @var list<string> 'photo' | 'video' (attachment kind) */
    public array $filterMedia = [];

    public function toggleArrayFilter(string $key, string $value): void
    {
        if (! in_array($key, ['filterTypes', 'filterStatuses', 'filterLabels', 'filterAuthors', 'filterMedia'], true)) {
            return;
        }

        $current = array_map('strval', $this->{$key});
        $this->{$key} = in_array($value, $current, true)
            ? array_values(array_diff($current, [$value]))
            : [...$current, $value];
    }

    public function clearPostFilters(): void
    {
        $this->filterTypes = [];
        $this->filterStatuses = [];
        $this->filterLabels = [];
        $this->filterAuthors = [];
        $this->filterMedia = [];
        $this->hiddenLocations = [];
        $this->hiddenNoteTags = [];
        session(['posts_hidden_note_tags' => []]);
    }

    public function activeFilterCount(): int
    {
        return count($this->filterTypes) + count($this->filterStatuses)
            + count($this->filterLabels) + count($this->filterAuthors)
            + count($this->filterMedia)
            + count($this->hiddenLocations) + count($this->hiddenNoteTags);
    }

    /** Distinct post authors for the filter (imported posts show as Google). */
    public function authorOptions(): array
    {
        return Post::query()->whereNotNull('created_by_name')->distinct()->orderBy('created_by_name')->pluck('created_by_name')->all();
    }

    /** @return array<int, string> label id => name */
    public function labelFilterOptions(): array
    {
        return PostLabel::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Restrict posts to the selected types/statuses/authors/labels. */
    protected function applyPostFilters(Builder $q): Builder
    {
        return $q
            ->when($this->filterTypes !== [], fn (Builder $qq) => $qq->whereIn('type', $this->filterTypes))
            ->when($this->filterStatuses !== [], fn (Builder $qq) => $qq->whereIn('status', $this->filterStatuses))
            ->when($this->filterAuthors !== [], fn (Builder $qq) => $qq->whereIn('created_by_name', $this->filterAuthors))
            ->when($this->filterLabels !== [], function (Builder $qq): Builder {
                return $qq->where(function (Builder $sub): void {
                    foreach (array_map('intval', $this->filterLabels) as $labelId) {
                        $sub->orWhereJsonContains('label_ids', $labelId);
                    }
                });
            })
            // Media kind: photo and/or video attached (union when both picked).
            ->when($this->filterMedia !== [], function (Builder $qq): Builder {
                return $qq->where(function (Builder $sub): void {
                    if (in_array('photo', $this->filterMedia, true)) {
                        $sub->orWhereNotNull('image_url');
                    }
                    if (in_array('video', $this->filterMedia, true)) {
                        $sub->orWhereNotNull('video_url');
                    }
                });
            });
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
        $this->replyingToCommentId = null;
        $this->editingCommentId = null;
        $this->editingCommentBody = '';
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

        ActivityLogger::log('post.labels_updated', ['label' => PostLabel::find($labelId)?->name], $post);
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

        // Replies must point at a real top-level comment on this post.
        $parent = $this->replyingToCommentId !== null
            ? PostComment::query()->where('post_id', $post->id)->whereNull('parent_id')->find($this->replyingToCommentId)
            : null;

        $comment = PostComment::create([
            'post_id' => $post->id,
            'parent_id' => $parent?->id,
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

    // ── Comment collaboration: edit, delete, reply, react ──────────────────

    /** The top-level comment the composer is replying to, or null. */
    public ?int $replyingToCommentId = null;

    public ?int $editingCommentId = null;

    public string $editingCommentBody = '';

    public function startReply(int $commentId): void
    {
        $comment = PostComment::query()->where('post_id', $this->viewingPostId)->find($commentId);

        // Replying to a reply threads under its top-level parent.
        $this->replyingToCommentId = $comment === null ? null : (int) ($comment->parent_id ?? $comment->id);
    }

    public function cancelReply(): void
    {
        $this->replyingToCommentId = null;
    }

    public function startEditComment(int $commentId): void
    {
        $comment = $this->ownComment($commentId);
        if ($comment === null) {
            return;
        }

        $this->editingCommentId = $commentId;
        $this->editingCommentBody = $comment->body;
    }

    public function cancelEditComment(): void
    {
        $this->editingCommentId = null;
        $this->editingCommentBody = '';
    }

    public function saveEditedComment(): void
    {
        $body = trim($this->editingCommentBody);
        $comment = $this->editingCommentId !== null ? $this->ownComment($this->editingCommentId) : null;

        if ($comment === null || $body === '') {
            return;
        }

        $comment->forceFill(['body' => $body, 'edited_at' => now()])->save();
        $this->cancelEditComment();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->ownComment($commentId);
        if ($comment === null) {
            return;
        }

        PostComment::query()->where('parent_id', $comment->id)->delete();
        $comment->delete();

        if ($this->replyingToCommentId === $commentId) {
            $this->replyingToCommentId = null;
        }
    }

    /** Toggle the signed-in user's emoji reaction on a comment. */
    public function toggleReaction(int $commentId, string $emoji): void
    {
        if (! in_array($emoji, PostComment::REACTION_EMOJI, true)) {
            return;
        }

        $comment = PostComment::query()->where('post_id', $this->viewingPostId)->find($commentId);
        $userId = (int) auth()->id();
        if ($comment === null || $userId === 0) {
            return;
        }

        $reactions = $comment->reactions ?? [];
        $existing = array_filter(
            $reactions,
            fn (array $r): bool => ($r['emoji'] ?? '') === $emoji && (int) ($r['user_id'] ?? 0) === $userId,
        );

        $reactions = $existing !== []
            ? array_values(array_filter(
                $reactions,
                fn (array $r): bool => ! (($r['emoji'] ?? '') === $emoji && (int) ($r['user_id'] ?? 0) === $userId),
            ))
            : [...$reactions, ['emoji' => $emoji, 'user_id' => $userId, 'user_name' => (string) auth()->user()?->name]];

        $comment->forceFill(['reactions' => $reactions])->save();

        // Only adding a reaction is feed-worthy; removals would just be noise.
        if ($existing === [] && ($post = Post::find($this->viewingPostId)) !== null) {
            ActivityLogger::log('post.comment_reacted', ['emoji' => $emoji], $post);
        }
    }

    /** The comment, but only when the signed-in user wrote it. */
    private function ownComment(int $commentId): ?PostComment
    {
        $comment = PostComment::query()->where('post_id', $this->viewingPostId)->find($commentId);

        return $comment !== null && (int) $comment->user_id === (int) auth()->id() ? $comment : null;
    }

    /** "Replying to {name}" strip above the composer while a reply is armed. */
    private function replyBannerHtml(): string
    {
        $parent = $this->replyingToCommentId !== null
            ? PostComment::query()->where('post_id', $this->viewingPostId)->find($this->replyingToCommentId)
            : null;

        if ($parent === null) {
            return '';
        }

        return '<div class="fp-reply-banner">'
            .'<span style="display:inline-flex;align-items:center;gap:.3rem;">'.$this->icon('o-arrow-uturn-left').' '.e(__('pages/posts.comment_replying_to', ['name' => (string) ($parent->user_name ?? '?')])).'</span>'
            .'<button type="button" class="fp-link" wire:click="cancelReply">'.$this->icon('o-x-mark').'</button>'
            .'</div>';
    }

    // ── Share & duplicate (the "…" menu) ────────────────────────────────────

    /** The kebab menu shown in the post dialogs' footers and list rows. */
    private function postMoreMenu(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('shareMenu')
                ->label(__('pages/posts.share'))
                ->icon(Heroicon::OutlinedShare)
                ->action(fn () => $this->replaceMountedAction('sharePost')),
            Action::make('duplicateMenu')
                ->label(__('pages/posts.duplicate_to'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->action(fn () => $this->replaceMountedAction('duplicateTo')),
        ])
            ->icon(Heroicon::OutlinedEllipsisVertical)
            ->color('gray');
    }

    private function postShare(): ?PostShare
    {
        return PostShare::query()
            ->where('workspace_id', (string) session('current_workspace_id'))
            ->where('post_id', $this->viewingPostId)
            ->first();
    }

    /** Get-or-create the post's single share link (no password, no window). */
    private function ensurePostShare(): ?PostShare
    {
        $post = Post::find($this->viewingPostId);
        if ($post === null) {
            return null;
        }

        return PostShare::firstOrCreate(
            ['workspace_id' => (string) session('current_workspace_id'), 'post_id' => $post->id],
            [
                'token' => Str::random(48),
                'title' => (string) ($post->title ?: Str::limit((string) $post->caption, 60)),
                'html' => $this->shareHtmlFor($post),
            ],
        );
    }

    /** Standalone public page for a shared post (snapshot, like report shares). */
    private function shareHtmlFor(Post $post): string
    {
        $business = e($this->businessNameLabel($post->location_ids ?? []));

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.$business.'</title>'
            .'<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; margin:0; padding:2rem 1rem; display:flex; justify-content:center; } .wrap { width:100%; max-width:26rem; }</style>'
            .'</head><body><div class="wrap">'
            .$this->postDetailsHtml($post->id)
            .'</div></body></html>';
    }

    public function sharePostAction(): Action
    {
        return Action::make('sharePost')
            ->modalHeading(__('pages/posts.share_heading'))
            ->modalDescription(__('pages/posts.share_desc'))
            ->modalWidth(Width::Medium)
            ->modalSubmitActionLabel(__('pages/posts.share_save'))
            ->modalCancelActionLabel(__('pages/posts.close'))
            // Get-or-create as the modal opens so the link shows right away.
            ->fillForm(function (): array {
                $share = $this->ensurePostShare();

                return [
                    'access_from' => $share?->access_from?->format('Y-m-d H:i'),
                    'access_until' => $share?->access_until?->format('Y-m-d H:i'),
                ];
            })
            ->schema(fn (): array => [
                Placeholder::make('current_link')
                    ->label(__('pages/posts.share_link'))
                    ->content(function (): HtmlString {
                        $share = $this->ensurePostShare();
                        $url = e($share !== null ? route('posts.shared', $share->token) : '');

                        return new HtmlString(
                            '<div x-data="{ copied: false }" style="display:flex; align-items:center; gap:8px;">'
                            .'<a href="'.$url.'" target="_blank" style="color:#2d19ec; word-break:break-all; flex:1; font-size:13px;">'.$url.'</a>'
                            .'<button type="button" @click="navigator.clipboard.writeText(\''.$url.'\'); copied = true; setTimeout(() => copied = false, 1500)"'
                            .' style="flex:none; display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border:1px solid #e5e7eb; border-radius:8px; background:transparent; cursor:pointer; font-size:12px; white-space:nowrap; color:inherit;">'
                            .svg('heroicon-o-clipboard')->toHtml()
                            .'<span x-text="copied ? '.Js::from(__('pages/posts.share_copied')).' : '.Js::from(__('pages/posts.share_copy')).'">'.e(__('pages/posts.share_copy')).'</span>'
                            .'</button></div>'
                            .'<style>.fi-modal a + button svg { width: 14px; height: 14px; }</style>'
                        );
                    }),

                TextInput::make('password')
                    ->label(__('pages/posts.share_password'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true'])
                    ->helperText(__('pages/posts.share_password_help'))
                    ->hintAction(
                        Action::make('generateSharePassword')
                            ->label(__('pages/posts.share_generate'))
                            ->icon(Heroicon::OutlinedSparkles)
                            ->action(fn ($set) => $set('password', Str::password(12, symbols: false))),
                    ),

                Grid::make(2)->schema([
                    DateTimePicker::make('access_from')->label(__('pages/posts.share_from'))->seconds(false),
                    DateTimePicker::make('access_until')->label(__('pages/posts.share_until'))->seconds(false)->afterOrEqual('access_from'),
                ]),
            ])
            ->extraModalFooterActions(fn (): array => [
                Action::make('revokeShare')
                    ->label(__('pages/posts.share_revoke'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (): bool => $this->postShare() !== null)
                    ->action(function (): void {
                        $this->postShare()?->delete();
                        Notification::make()->title(__('pages/posts.share_revoked'))->success()->send();
                    })
                    ->cancelParentActions(),
            ])
            ->action(function (array $data): void {
                $post = Post::find($this->viewingPostId);
                $existing = $this->postShare();
                if ($post === null) {
                    return;
                }

                PostShare::updateOrCreate(
                    ['workspace_id' => (string) session('current_workspace_id'), 'post_id' => $post->id],
                    [
                        'token' => $existing?->token ?? Str::random(48),
                        'title' => (string) ($post->title ?: Str::limit((string) $post->caption, 60)),
                        // Refresh the snapshot so the link shows the post as it is now.
                        'html' => $this->shareHtmlFor($post),
                        'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : $existing?->password,
                        'access_from' => $data['access_from'] ?? null,
                        'access_until' => $data['access_until'] ?? null,
                    ],
                );

                ActivityLogger::log('post.shared', [], $post);
                Notification::make()->title(__('pages/posts.share_saved'))->success()->send();
            });
    }

    /** Copy the open post into this or another workspace as a fresh draft. */
    public function duplicateToAction(): Action
    {
        return Action::make('duplicateTo')
            ->modalHeading(__('pages/posts.duplicate_to_heading'))
            ->modalDescription(__('pages/posts.duplicate_to_desc'))
            ->modalWidth(Width::Medium)
            ->modalSubmitActionLabel(__('pages/posts.duplicate_to_submit'))
            ->schema([
                Select::make('workspace_id')
                    ->label(__('pages/posts.duplicate_to_workspace'))
                    ->options(fn (): array => auth()->user()?->workspaces()->orderBy('name')->pluck('name', 'tenants.id')->all() ?? [])
                    ->default(fn (): ?string => (string) session('current_workspace_id'))
                    ->required()
                    ->selectablePlaceholder(false),
            ])
            ->action(fn (array $data) => $this->duplicatePostTo((string) $data['workspace_id']));
    }

    /** Duplicate into the picked workspace (cross-tenant when it's another one). */
    public function duplicatePostTo(string $workspaceId): void
    {
        $post = Post::find($this->viewingPostId);
        $target = auth()->user()?->workspaces()->whereKey($workspaceId)->first();

        if ($post === null || ! $target instanceof Workspace) {
            return;
        }

        if ((string) $target->getKey() === (string) session('current_workspace_id')) {
            $this->duplicateAsDraft($post->id);

            return;
        }

        // Copy the content only: locations, source ids and labels are
        // workspace-local, so the copy starts unassigned in the target.
        $attributes = [
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
            'location_ids' => [],
            'source_ids' => [],
            'status' => 'draft',
            'origin' => 'app',
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()?->name,
        ];

        $previous = tenant();

        try {
            tenancy()->initialize($target);
            $copy = Post::create($attributes);
            ActivityLogger::log('post.duplicated', ['from_workspace' => (string) $previous?->getKey()], $copy);
        } finally {
            $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
        }

        Notification::make()
            ->title(__('pages/posts.duplicate_to_done', ['workspace' => $target->name]))
            ->success()
            ->send();
    }

    /** Details modal for a calendar card. */
    public function viewPostAction(): Action
    {
        return Action::make('viewPost')
            // The heading slot carries the labels control (reference-style);
            // the type name stays as the screen-reader title.
            ->modalHeading(fn (): HtmlString => $this->labelsHeadingHtml(__('pages/posts.type_'.(Post::find($this->viewingPostId)?->type ?? 'update'))))
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
                $this->postMoreMenu(),
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
            ->modalHeading(fn (): HtmlString => $this->labelsHeadingHtml(__('pages/posts.draft_heading')))
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
                $this->postMoreMenu(),
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

        // Members the author can @-mention (everyone but themselves), embedded
        // for the Alpine autocomplete that opens on typing "@".
        $selfId = (int) auth()->id();
        $members = [];
        foreach ($this->workspaceMembers() as $id => $name) {
            if ((int) $id !== $selfId) {
                $members[] = ['id' => (int) $id, 'name' => (string) $name, 'a' => $this->memberAvatars()[(int) $id] ?? null];
            }
        }
        $membersJson = htmlspecialchars((string) json_encode($members), ENT_QUOTES);

        $fileChips = '';
        foreach ($this->commentFiles as $file) {
            $fileChips .= '<span class="fp-file">'.$this->icon('o-paper-clip').' '.e($file->getClientOriginalName()).'</span>';
        }

        $count = PostComment::query()->where('post_id', $postId)->count();

        // The @-mention autocomplete: watch the caret for an "@fragment", offer
        // matching members, insert "@Name " and defer-sync the picked ids.
        $mention = 'x-data="{ open: false, active: 0, q: \'\', start: 0, members: '.$membersJson.','
            .' get items() { return this.members.filter(m => m.name.toLowerCase().includes(this.q)).slice(0, 6) },'
            .' name(id) { const m = this.members.find(x => x.id === Number(id)); return m ? m.name : \'\' },'
            .' scan() { const ta = this.$refs.ta; const upto = ta.value.slice(0, ta.selectionStart); const m = upto.match(/@([^\s@]{0,30})$/u);'
            .' if (m) { this.q = m[1].toLowerCase(); this.start = upto.length - m[1].length; this.active = 0; this.open = this.items.length > 0 } else { this.open = false } },'
            .' pick(m) { if (! m) { return } const ta = this.$refs.ta; const before = ta.value.slice(0, this.start); const after = ta.value.slice(ta.selectionStart);'
            .' ta.value = before + m.name + \' \' + after; ta.dispatchEvent(new Event(\'input\', { bubbles: true }));'
            .' const ids = [...new Set([...(this.$wire.commentMentions || []).map(Number), m.id])]; this.$wire.set(\'commentMentions\', ids, false);'
            .' this.open = false; const p = (before + m.name + \' \').length; this.$nextTick(() => { ta.focus(); ta.setSelectionRange(p, p) }) },'
            .' drop(id) { this.$wire.set(\'commentMentions\', (this.$wire.commentMentions || []).map(Number).filter(i => i !== Number(id)), false) } }"';

        return '<div x-data="{ tab: \'comments\' }" class="fp-panel '.($bordered ? 'fp-bordered' : 'fp-stacked').'">'
            .$this->feedbackPanelCss()
            // Tabs
            .'<div class="fp-tabs">'
            .'<button type="button" @click="tab = \'comments\'" :class="tab === \'comments\' ? \'active\' : \'\'">'.e(__('pages/posts.comments')).($count > 0 ? ' <span class="fp-count">'.$count.'</span>' : '').'</button>'
            .'<button type="button" @click="tab = \'activity\'" :class="tab === \'activity\' ? \'active\' : \'\'">'.e(__('pages/posts.activity_title')).'</button>'
            .'</div>'
            // Comments tab: thread + composer card
            .'<div x-show="tab === \'comments\'">'
            .$this->commentsHtml($postId)
            .$this->replyBannerHtml()
            .'<div class="fp-composer" '.$mention.'>'
            .'<textarea x-ref="ta" wire:model="commentBody" rows="3" placeholder="'.e(__('pages/posts.comment_placeholder')).'"'
            .' @input="scan()" @click="scan()"'
            .' @keydown.down="if (open) { $event.preventDefault(); active = Math.min(active + 1, items.length - 1) }"'
            .' @keydown.up="if (open) { $event.preventDefault(); active = Math.max(active - 1, 0) }"'
            .' @keydown.enter="if (open) { $event.preventDefault(); pick(items[active]) }"'
            .' @keydown.escape="open = false"'
            .'></textarea>'
            // Suggestion dropdown, anchored to the composer card.
            .'<div class="fp-mention-pop" x-show="open" x-cloak @click.outside="open = false">'
            .'<template x-for="(m, i) in items" :key="m.id">'
            .'<button type="button" class="fp-mention-item" :class="i === active ? \'active\' : \'\'" @mouseenter="active = i" @click="pick(m)">'
            .'<template x-if="m.a"><img class="fp-avatar" :src="m.a" alt="" /></template>'
            .'<template x-if="! m.a"><span class="fp-avatar fp-avatar-solid" x-text="m.name.charAt(0).toUpperCase()"></span></template>'
            .'<span x-text="m.name"></span>'
            .'</button>'
            .'</template>'
            .'</div>'
            // Picked mentions as removable chips.
            .'<div class="fp-picked" x-show="($wire.commentMentions || []).length" x-cloak>'
            .'<template x-for="id in ($wire.commentMentions || [])" :key="id">'
            .'<span class="fp-file">@<span x-text="name(id)"></span> <button type="button" class="fp-link" @click="drop(id)">'.$this->icon('o-x-mark').'</button></span>'
            .'</template>'
            .'</div>'
            .($fileChips !== '' ? '<div class="fp-files">'.$fileChips.' <button type="button" class="fp-link" wire:click="$set(\'commentFiles\', [])">'.$this->icon('o-x-mark').'</button></div>' : '')
            .'<div class="fp-composer-bar">'
            .'<label class="fp-attach" title="'.e(__('pages/posts.comment_attachments')).'">'
            .'<input type="file" wire:model="commentFiles" multiple>'
            .$this->icon('o-paper-clip')
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

    /**
     * The labels control that replaces the dialog heading (reference-style):
     * assigned chips + an Edit trigger with the in-place popover. The original
     * title stays for screen readers only.
     */
    private function labelsHeadingHtml(string $srTitle): HtmlString
    {
        $post = Post::find($this->viewingPostId);
        if ($post === null) {
            return new HtmlString(e($srTitle));
        }

        $chips = PostLabel::query()->whereIn('id', $post->label_ids ?? [])->get()
            ->map(function (PostLabel $label): string {
                [$bg, $accent] = PostLabel::COLORS[$label->color] ?? PostLabel::COLORS['blue'];

                return '<span class="fp-chip" style="background:'.$bg.'; color:'.$accent.';">'.e($label->name).'</span>';
            })->implode('');

        return new HtmlString(
            $this->feedbackPanelCss()
            .'<span class="fp-sr">'.e($srTitle).'</span>'
            .'<div class="fp-labels" style="margin-bottom:0; font-weight:400;">'
            .$chips
            .'<span style="position:relative;">'
            .'<button type="button" class="fp-labels-btn" wire:click="toggleLabelsPopover">'
            .$this->icon('o-tag')
            .e(__('pages/posts.labels_assign'))
            .'</button>'
            .($this->labelsPopoverOpen ? $this->labelsPopoverHtml($post) : '')
            .'</span>'
            .'</div>'
        );
    }

    /** The "Add labels" popover: check to assign, pencil to edit, trash to
     *  delete, plus inline create with a color dot picker. */
    private function labelsPopoverHtml(Post $post): string
    {
        $assigned = array_map('intval', $post->label_ids ?? []);

        $rows = PostLabel::query()->orderBy('name')->get()->map(function (PostLabel $label) use ($assigned): string {
            [$bg, $accent] = PostLabel::COLORS[$label->color] ?? PostLabel::COLORS['blue'];
            $id = (int) $label->getKey();

            // Row flips to an inline editor while being edited: name on top,
            // then color dots left + save/cancel/delete right.
            if ($this->editingLabelId === $id) {
                return '<div class="fp-pop-edit">'
                    .'<input type="text" wire:model="editingLabelName" wire:keydown.enter="saveEditedLabel" />'
                    .'<div class="fp-pop-tools">'
                    .$this->colorDotsHtml('editingLabelColor', $this->editingLabelColor)
                    .'<span class="fp-pop-row-actions">'
                    .'<button type="button" class="fp-pop-act fp-pop-act-primary" wire:click="saveEditedLabel" title="'.e(__('pages/posts.comment_save')).'">'.$this->icon('o-check').'</button>'
                    .'<button type="button" class="fp-pop-act" wire:click="$set(\'editingLabelId\', null)" title="'.e(__('pages/posts.comment_cancel')).'">'.$this->icon('o-x-mark').'</button>'
                    .'<button type="button" class="fp-pop-act fp-danger" wire:click="deleteLabel('.$id.')" title="'.e(__('pages/posts.comment_delete')).'">'.$this->icon('o-trash').'</button>'
                    .'</span>'
                    .'</div>'
                    .'</div>';
            }

            return '<div class="fp-pop-row">'
                .'<label class="fp-pop-check">'
                .'<input type="checkbox" wire:click="togglePostLabel('.$id.')" '.(in_array($id, $assigned, true) ? 'checked' : '').'>'
                .'<span class="fp-chip" style="background:'.$bg.'; color:'.$accent.';">'.e($label->name).'</span>'
                .'</label>'
                .'<button type="button" class="fp-pop-gear" wire:click="startEditLabel('.$id.')" title="'.e(__('pages/posts.labels_edit')).'">'
                .$this->icon('o-pencil')
                .'</button>'
                .'</div>';
        })->implode('');

        return '<div class="fp-pop" x-data @click.outside="$wire.set(\'labelsPopoverOpen\', false)">'
            .'<div class="fp-pop-title">'.e(__('pages/posts.labels_assign_title')).'</div>'
            .($rows !== '' ? $rows : '<div class="fp-muted" style="padding:.3rem 0 .5rem;">'.e(__('pages/posts.labels_none')).'</div>')
            .'<div class="fp-pop-create">'
            .'<div class="fp-pop-create-row">'
            .'<input type="text" wire:model="newLabelName" wire:keydown.enter="createLabelInline" placeholder="'.e(__('pages/posts.labels_create_placeholder')).'" />'
            .'<button type="button" class="fp-pop-act fp-pop-act-primary" wire:click="createLabelInline" title="'.e(__('pages/posts.labels_add')).'">'.$this->icon('o-plus').'</button>'
            .'</div>'
            .$this->colorDotsHtml('newLabelColor', $this->newLabelColor)
            .'</div>'
            .'</div>';
    }

    /** Dot colors distinct enough to tell apart (the chip accents make yellow
     *  and orange near-identical). */
    private const DOT_COLORS = [
        'yellow' => '#eab308', 'orange' => '#f97316', 'red' => '#ef4444',
        'pink' => '#ec4899', 'purple' => '#a855f7', 'blue' => '#3b82f6',
        'teal' => '#14b8a6', 'green' => '#22c55e', 'gray' => '#71717a',
    ];

    /** A row of clickable color dots bound to a Livewire property. */
    private function colorDotsHtml(string $property, string $selected): string
    {
        $dots = '';
        foreach (array_keys(PostLabel::COLORS) as $key) {
            $dots .= '<button type="button" class="fp-dot'.($key === $selected ? ' active' : '').'" style="--dot:'.(self::DOT_COLORS[$key] ?? '#3b82f6').';" wire:click="$set(\''.$property.'\', \''.$key.'\')" title="'.e(__('pages/posts.color_'.$key)).'"></button>';
        }

        return '<span class="fp-dots">'.$dots.'</span>';
    }

    /** Official Heroicon markup for the hand-built panel HTML (no ad-hoc paths). */
    private function icon(string $name): string
    {
        return svg('heroicon-'.$name)->toHtml();
    }

    /** Workspace member avatars (user id => URL), one query per request. */
    private function memberAvatars(): array
    {
        return once(function (): array {
            $workspace = tenant();
            if (! $workspace instanceof Workspace) {
                return [];
            }

            return $workspace->users()->get()
                ->mapWithKeys(fn (User $user): array => [(int) $user->id => $user->getFilamentAvatarUrl()])
                ->filter()
                ->all();
        });
    }

    /** The user's photo when they have one, otherwise their initial. */
    private function avatarHtml(?int $userId, string $name): string
    {
        $url = $userId !== null ? ($this->memberAvatars()[$userId] ?? null) : null;

        return $url !== null
            ? '<img class="fp-avatar" src="'.e($url).'" alt="" loading="lazy" />'
            : '<span class="fp-avatar">'.e(mb_strtoupper(mb_substr($name, 0, 1))).'</span>';
    }

    /** Scoped styles for the feedback panel (light + dark). Emitted once per panel. */
    private function feedbackPanelCss(): string
    {
        return <<<'HTML'
            <style>
                .fp-panel { font-size: .85rem; }
                .fp-bordered { border-left: 1px solid #eceef2; padding-left: 1.4rem; min-height: 20rem; }
                .fp-stacked { border-top: 1px solid #eceef2; padding-top: 1rem; }
                /* Stacked single-column layout on small screens: no divider line. */
                @media (max-width: 1023px) { .fp-bordered { border-left: none; padding-left: 0; min-height: 0; } }
                .dark .fp-bordered { border-color: rgb(255 255 255 / .08); }
                .dark .fp-stacked { border-color: rgb(255 255 255 / .08); }
                .fp-muted { color: #9ca3af; font-size: .78rem; }
                .fp-link { background: none; border: none; cursor: pointer; color: #2d19ec; font-size: .74rem; font-weight: 600; padding: .1rem .25rem; }
                .dark .fp-link { color: #a5b4fc; }
                .fp-labels { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; margin-bottom: .8rem; }
                .fp-chip { display: inline-flex; align-items: center; box-sizing: border-box; height: 1.5rem; font-size: .68rem; font-weight: 700; letter-spacing: .02em; padding: 0 .55rem; border-radius: 999px; }
                .fp-labels-btn { display: inline-flex; align-items: center; box-sizing: border-box; height: 1.45rem; gap: .3rem; font-size: .7rem; font-weight: 600; color: #374151; background: none; border: 1px dashed #c8ccd2; border-radius: 999px; padding: 0 .6rem; line-height: 1; cursor: pointer; transition: all .12s ease; }
                .fp-labels-btn:hover { border-color: #2d19ec; color: #2d19ec; }
                .fp-labels-btn svg { width: .8rem; height: .8rem; }
                .dark .fp-labels-btn { color: #d4d4d8; border-color: rgb(255 255 255 / .22); }
                .dark .fp-labels-btn:hover { border-color: #a5b4fc; color: #a5b4fc; }
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
                /* No inner scrollbox: the dialog scrolls, and clipping here cut
                   off the reaction/menu popovers on the last comments. */
                .fp-thread { margin-bottom: .6rem; }
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
                .fp-composer { position: relative; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; }
                .dark .fp-composer { border-color: rgb(255 255 255 / .12); background: rgb(255 255 255 / .04); }
                .fp-composer:focus-within { border-color: #2d19ec66; }
                .fp-composer textarea { display: block; width: 100%; border: none; outline: none; background: transparent; resize: none; padding: .6rem .75rem .3rem; font-size: .85rem; color: inherit; }
                .fp-sr { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
                /* Anchored right under the first line of the textarea, near the "@". */
                .fp-mention-pop { position: absolute; top: 2.4rem; left: .75rem; right: .75rem; z-index: 40; background: #fff; border: 1px solid #e5e7eb; border-radius: .6rem; box-shadow: 0 12px 32px -8px rgb(0 0 0 / .18); padding: .3rem; max-height: 12rem; overflow: auto; }
                .dark .fp-mention-pop { background: #1b1b21; border-color: rgb(255 255 255 / .12); }
                .fp-mention-item { display: flex; align-items: center; gap: .5rem; width: 100%; text-align: left; background: none; border: none; cursor: pointer; padding: .35rem .5rem; border-radius: .45rem; font-size: .82rem; color: inherit; }
                .fp-mention-item.active { background: #eef2ff; }
                .dark .fp-mention-item.active { background: rgb(99 102 241 / .18); }
                .fp-mention-item .fp-avatar { width: 1.5rem; height: 1.5rem; font-size: .68rem; }
                /* Initial fallback must stay visible on the highlighted row. */
                .fp-avatar-solid { background: #2d19ec; color: #fff; }
                img.fp-avatar { object-fit: cover; }
                .fp-picked { padding: .1rem .6rem 0; }
                .fp-files { padding: .2rem .6rem 0; }
                /* Comment hover actions, menus, reactions, replies */
                .fp-comment-head { display: flex; align-items: center; gap: .35rem; }
                .fp-c-actions { display: inline-flex; align-items: center; gap: .05rem; margin-left: auto; opacity: 0; transition: opacity .12s ease; }
                .fp-comment:hover .fp-c-actions { opacity: 1; }
                .fp-c-btn { background: none; border: none; cursor: pointer; color: #9ca3af; padding: .18rem; border-radius: .35rem; display: inline-grid; place-items: center; }
                .fp-c-btn:hover { color: #2d19ec; background: #eef2ff; }
                .dark .fp-c-btn:hover { color: #a5b4fc; background: rgb(99 102 241 / .15); }
                .fp-c-btn svg { width: .95rem; height: .95rem; }
                .fp-menu { position: absolute; right: 0; top: 1.5rem; z-index: 35; min-width: 8rem; background: #fff; border: 1px solid #e5e7eb; border-radius: .55rem; box-shadow: 0 10px 26px -6px rgb(0 0 0 / .18); padding: .25rem; display: flex; flex-direction: column; }
                .dark .fp-menu { background: #1b1b21; border-color: rgb(255 255 255 / .12); }
                .fp-menu button { background: none; border: none; cursor: pointer; text-align: left; font-size: .8rem; padding: .35rem .5rem; border-radius: .4rem; color: inherit; }
                .fp-menu button:hover { background: #f4f5f7; }
                .dark .fp-menu button:hover { background: rgb(255 255 255 / .06); }
                .fp-react-pop { position: absolute; right: 0; top: 1.5rem; z-index: 35; background: #fff; border: 1px solid #e5e7eb; border-radius: 999px; box-shadow: 0 10px 26px -6px rgb(0 0 0 / .18); padding: .2rem .3rem; display: inline-flex; gap: .05rem; }
                .dark .fp-react-pop { background: #1b1b21; border-color: rgb(255 255 255 / .12); }
                .fp-react-pop button { background: none; border: none; cursor: pointer; font-size: .95rem; padding: .15rem .25rem; border-radius: 999px; }
                .fp-react-pop button:hover { background: #eef2ff; transform: scale(1.2); }
                .fp-reactions { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .15rem; }
                .fp-reaction { display: inline-flex; align-items: center; gap: .25rem; background: #f4f5f7; border: 1px solid transparent; border-radius: 999px; padding: .08rem .45rem; font-size: .78rem; cursor: pointer; }
                .fp-reaction span { font-size: .68rem; color: #6b7280; font-weight: 600; }
                .fp-reaction.mine { background: #eef2ff; border-color: #2d19ec55; }
                .dark .fp-reaction { background: rgb(255 255 255 / .07); }
                .dark .fp-reaction.mine { background: rgb(99 102 241 / .2); border-color: #a5b4fc66; }
                .fp-replies { margin-left: 2.4rem; border-left: 2px solid #f1f2f4; padding-left: .6rem; }
                .dark .fp-replies { border-color: rgb(255 255 255 / .08); }
                .fp-reply-banner { display: flex; align-items: center; justify-content: space-between; font-size: .74rem; line-height: 1; color: #6b7280; background: #f4f5f7; border-radius: .5rem .5rem 0 0; padding: .38rem .6rem; margin-bottom: -.35rem; }
                .fp-reply-banner > span { display: inline-flex; align-items: center; gap: .3rem; }
                .fp-reply-banner svg { display: block; }
                .dark .fp-reply-banner { background: rgb(255 255 255 / .06); color: #a1a1aa; }
                .fp-edited { font-size: .68rem; color: #9ca3af; }
                /* Heroicon sizing for the hand-built panel markup */
                .fp-file svg { width: .7rem; height: .7rem; flex: none; }
                .fp-link svg { width: .8rem; height: .8rem; display: inline-block; vertical-align: -.12em; }
                .fp-menu button { display: flex; align-items: center; gap: .4rem; }
                .fp-menu button svg { width: .85rem; height: .85rem; flex: none; }
                .fp-reply-banner svg { width: .8rem; height: .8rem; flex: none; }
                .fp-c-edit { display: block; }
                .fp-c-edit textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .4rem .55rem; font-size: .82rem; background: transparent; color: inherit; }
                .dark .fp-c-edit textarea { border-color: rgb(255 255 255 / .14); }
                .fp-c-edit-bar { display: flex; justify-content: flex-end; gap: .4rem; margin-top: .3rem; }
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
                .fp-pop-edit { display: grid; gap: .45rem; padding: .35rem 0 .5rem; }
                .fp-pop-edit input[type="text"], .fp-pop-create input[type="text"] { width: 100%; min-width: 0; border: 1px solid #e5e7eb; border-radius: .45rem; padding: .32rem .55rem; font-size: .78rem; background: transparent; color: inherit; }
                .dark .fp-pop-edit input[type="text"], .dark .fp-pop-create input[type="text"] { border-color: rgb(255 255 255 / .14); }
                /* Dots left, actions right, one tidy row. */
                .fp-pop-tools { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
                .fp-pop-row-actions { display: inline-flex; gap: .15rem; flex: none; }
                .fp-pop-act { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: .45rem; border: 1px solid #e5e7eb; background: none; color: #6b7280; cursor: pointer; }
                .fp-pop-act svg { width: .85rem; height: .85rem; }
                .fp-pop-act:hover { background: #f4f5f7; }
                .fp-pop-act-primary { background: #2d19ec; border-color: #2d19ec; color: #fff; }
                .fp-pop-act-primary:hover { background: #2413c9; }
                .fp-pop-act.fp-danger { color: #dc2626; }
                .dark .fp-pop-act { border-color: rgb(255 255 255 / .14); color: #a1a1aa; }
                .dark .fp-pop-act:hover { background: rgb(255 255 255 / .06); }
                .fp-danger { color: #dc2626; }
                .fp-pop-create { display: grid; gap: .45rem; margin-top: .5rem; padding-top: .6rem; border-top: 1px solid #f1f2f4; }
                .dark .fp-pop-create { border-color: rgb(255 255 255 / .08); }
                .fp-pop-create-row { display: flex; align-items: center; gap: .4rem; }
                .fp-pop-create-row input { flex: 1; }
                .fp-dots { display: inline-flex; gap: .35rem; flex: none; }
                .fp-dot { width: .8rem; height: .8rem; border-radius: 999px; background: var(--dot); border: none; cursor: pointer; padding: 0; transition: transform .1s ease; }
                .fp-dot:hover { transform: scale(1.2); }
                /* Selection ring offset from the dot, readable in both themes. */
                .fp-dot.active { box-shadow: 0 0 0 2px #fff, 0 0 0 3.5px var(--dot); }
                .dark .fp-dot.active { box-shadow: 0 0 0 2px #1b1b21, 0 0 0 3.5px var(--dot); }
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

            // Reaction entries carry the emoji itself; show it in the line.
            if (filled($entry->meta['emoji'] ?? null)) {
                $label .= ' '.$entry->meta['emoji'];
            }

            $whoRaw = (string) ($entry->user_name ?? __('pages/posts.activity_system'));
            $who = e($whoRaw);
            $when = e($entry->created_at?->diffForHumans() ?? '');

            // Same avatar + name layout as the comment thread, so both feeds read alike.
            return '<div class="fp-comment">'
                .$this->avatarHtml($entry->user_id !== null ? (int) $entry->user_id : null, $whoRaw)
                .'<span class="fp-comment-main">'
                .'<span class="fp-comment-head"><strong>'.$who.'</strong> '.e($label).'</span>'
                .'<span class="fp-comment-head"><span>'.$when.'</span></span>'
                .'</span>'
                .'</div>';
        })->implode('');

        return '<div class="fp-thread">'.$rows.'</div>';
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
            ->query(fn (): Builder => Post::query()->tap(fn (Builder $q) => $this->applyLocationFilter($q))->tap(fn (Builder $q) => $this->applyPostFilters($q)))
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
                // Same routing as a calendar card: drafts open the editable
                // composer (edit/delete live there), the rest the view dialog.
                // replaceMountedAction, NOT showPost/mountAction: a modal
                // mounted from inside a running table action is torn down again
                // when that action unmounts, so Edit appeared to do nothing.
                Action::make('view')
                    ->label(fn (Post $record): string => $record->status === 'draft' ? __('pages/posts.edit') : __('pages/posts.view'))
                    ->icon(fn (Post $record) => $record->status === 'draft' ? Heroicon::OutlinedPencilSquare : Heroicon::OutlinedEye)
                    ->color('gray')
                    ->action(function (Post $record): void {
                        $this->viewingPostId = $record->id;
                        $this->resetCommentComposer();
                        $this->replaceMountedAction($record->status === 'draft' ? 'editDraft' : 'viewPost');
                    }),

                ActionGroup::make([
                    Action::make('shareRow')
                        ->label(__('pages/posts.share'))
                        ->icon(Heroicon::OutlinedShare)
                        ->action(function (Post $record): void {
                            $this->viewingPostId = $record->id;
                            $this->replaceMountedAction('sharePost');
                        }),
                    Action::make('duplicateRow')
                        ->label(__('pages/posts.duplicate_to'))
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->action(function (Post $record): void {
                            $this->viewingPostId = $record->id;
                            $this->replaceMountedAction('duplicateTo');
                        }),
                ])
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('gray'),

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
                ->maxSize(102400)
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
