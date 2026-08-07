<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/posts.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/posts.not_configured_body') }}</div>
        </div>
    @else
        <style>
            .pc-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
            .pc-nav { display:flex; align-items:center; gap:.4rem; }
            .pc-btn { border:1px solid rgb(0 0 0 / .12); background:transparent; border-radius:.5rem; padding:.35rem .7rem; font-size:.85rem; cursor:pointer; color:inherit; }
            .dark .pc-btn { border-color: rgb(255 255 255 / .18); }
            .pc-btn:hover { background: rgb(0 0 0 / .04); }
            .dark .pc-btn:hover { background: rgb(255 255 255 / .06); }
            .pc-month { font-weight:700; font-size:1.05rem; min-width:11rem; text-align:center; }
            .pc-toggle { display:inline-flex; border:1px solid rgb(0 0 0 / .12); border-radius:.55rem; overflow:hidden; }
            .dark .pc-toggle { border-color: rgb(255 255 255 / .18); }
            .pc-toggle button { border:0; background:transparent; padding:.35rem .8rem; font-size:.85rem; cursor:pointer; color:inherit; }
            .pc-toggle button.active { background:#2d19ec; color:#fff; }

            .pc-fcount { display:inline-flex; align-items:center; justify-content:center; min-width:1.02rem; height:1.02rem; border-radius:999px; background:#2d19ec; color:#fff; font-size:.62rem; font-weight:700; padding:0 .25rem; }
            /* Planable-style Filter popover: accordion sections with icons + counts */
            .pc-pop-filter { width:17.5rem; max-height:26rem; overflow-y:auto; padding:.5rem .55rem .6rem; }
            .pcf-title { display:flex; justify-content:space-between; align-items:center; padding:.15rem .3rem .45rem; font-size:.92rem; }
            .pcf-cap { font-size:.62rem; letter-spacing:.09em; text-transform:uppercase; color:#9ca3af; padding:.3rem .3rem .35rem; font-weight:700; }
            .pcf-sec { border-radius:.55rem; }
            .pcf-sec.open { background:rgb(0 0 0 / .045); }
            .dark .pcf-sec.open { background:rgb(255 255 255 / .05); }
            .pcf-head { display:flex; align-items:center; gap:.55rem; width:100%; background:none; border:none; cursor:pointer; padding:.5rem .55rem; font-size:.83rem; font-weight:500; color:inherit; border-radius:.55rem; text-align:left; }
            .pcf-head:hover { background:rgb(0 0 0 / .045); }
            .dark .pcf-head:hover { background:rgb(255 255 255 / .05); }
            .pcf-head > svg { width:1rem; height:1rem; flex:none; }
            .pcf-chev { margin-left:auto; display:inline-grid; place-items:center; transition:transform .15s ease; }
            .pcf-chev svg { width:.8rem; height:.8rem; opacity:.55; }
            .pcf-body { padding:.05rem .45rem .5rem 1.05rem; display:grid; gap:.1rem; }
            .pcf-row { display:flex; align-items:center; gap:.5rem; padding:.28rem .35rem; font-size:.8rem; cursor:pointer; border-radius:.4rem; }
            .pcf-row:hover { background:rgb(0 0 0 / .045); }
            .dark .pcf-row:hover { background:rgb(255 255 255 / .05); }
            .pcf-row input { cursor:pointer; accent-color:#2d19ec; flex:none; }
            .pcf-row .nm { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .pcf-row .cnt { margin-left:auto; color:#9ca3af; font-size:.72rem; }
            .pcf-foot { border-top:1px solid rgb(0 0 0 / .07); margin-top:.45rem; padding-top:.45rem; }
            .dark .pcf-foot { border-color:rgb(255 255 255 / .08); }
            .pcf-foot button { display:flex; align-items:center; gap:.5rem; width:100%; background:none; border:none; cursor:pointer; padding:.45rem .55rem; font-size:.8rem; font-weight:600; color:#2d19ec; border-radius:.5rem; text-align:left; }
            .pcf-foot button:hover { background:#eef2ff; }
            .dark .pcf-foot button { color:#a5b4fc; }
            .dark .pcf-foot button:hover { background:rgb(99 102 241 / .15); }
            .pcf-foot button svg { width:.9rem; height:.9rem; flex:none; }
            .pcf-foot .pc-fcount { margin-left:auto; }
            .pc-grid { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); border:1px solid rgb(0 0 0 / .08); border-radius:.75rem; background:#fff; }
            /* Phones: keep readable day cells and scroll the grid sideways. */
            @media (max-width:700px) {
                .pc-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; margin:0 -.5rem; padding:0 .5rem; }
                .pc-scroll .pc-grid { min-width:56rem; }
            }
            .dark .pc-grid { background:#18181b; border-color: rgb(255 255 255 / .1); }
            /* Weekday header row sticks to the top so it stays visible while scrolling. */
            .pc-dow { position:sticky; top:0; z-index:5; padding:.5rem .6rem; font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid rgb(0 0 0 / .08); background:#f3f4f6; }
            .dark .pc-dow { border-color: rgb(255 255 255 / .1); background:#26262b; color:#a1a1aa; }
            /* Weekends read a shade darker so they stand out from work days. */
            .pc-dow.weekend { background:#e5e7eb; color:#4b5563; }
            .dark .pc-dow.weekend { background:#303036; color:#d4d4d8; }
            .pc-day.weekend { background: rgb(0 0 0 / .035); }
            .dark .pc-day.weekend { background: rgb(255 255 255 / .045); }
            .pc-day { min-height:7.5rem; padding:.4rem; border-bottom:1px solid rgb(0 0 0 / .06); border-right:1px solid rgb(0 0 0 / .06); }
            .pc-grid.week .pc-day { min-height:22rem; }
            .pc-day:nth-child(7n) { border-right:0; }
            .dark .pc-day { border-color: rgb(255 255 255 / .08); }
            .pc-day.out { background: rgb(0 0 0 / .02); }
            .dark .pc-day.out { background: rgb(255 255 255 / .02); }
            .pc-daynum { font-size:.75rem; color:#6b7280; margin:0 0 .3rem .15rem; }
            .pc-day.out .pc-daynum { color:#c4c4cc; }
            .dark .pc-day.out .pc-daynum { color:#52525b; }
            .pc-daynum .today { display:inline-flex; align-items:center; justify-content:center; min-width:1.35rem; height:1.35rem; border-radius:999px; background:#2d19ec; color:#fff; font-weight:700; }

            .pc-add { display:flex; gap:.3rem; margin-bottom:.3rem; opacity:0; transition:opacity .12s; }
            .pc-day:hover .pc-add, .pc-day:focus-within .pc-add { opacity:1; }
            .pc-add button { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:.28rem; border:1px dashed rgb(0 0 0 / .25); border-radius:.45rem; font-size:.72rem; padding:.22rem 0; color:#6b7280; background:transparent; cursor:pointer; }
            .dark .pc-add button { border-color: rgb(255 255 255 / .25); color:#a1a1aa; }
            .pc-add button:hover { border-color:#2d19ec; color:#2d19ec; }

            .pc-evt { display:flex; align-items:flex-start; gap:.3rem; font-size:.68rem; line-height:1.25; border-radius:.35rem; padding:.15rem .35rem; margin-bottom:.25rem; }
            .pc-evt .dot { flex:none; width:.45rem; height:.45rem; border-radius:999px; margin-top:.22rem; }
            .pc-evt span.t { min-width:0; overflow-wrap:break-word; }

            .pc-card { display:block; width:100%; text-align:left; border:1px solid rgb(0 0 0 / .08); border-left-width:3px; border-radius:.45rem; padding:.3rem .4rem; margin-bottom:.3rem; background:#fff; cursor:pointer; }
            .dark .pc-card { background:#232326; border-color: rgb(255 255 255 / .1); }
            .pc-card:hover { border-color:#2d19ec55; }
            .pc-card.draft { border-style:dashed; }
            .pc-card img { width:100%; height:2.6rem; object-fit:cover; border-radius:.3rem; margin-bottom:.25rem; -webkit-user-drag:none; user-select:none; }
            .pc-thumb video { -webkit-user-drag:none; }
            .pc-thumb { position:relative; display:block; margin-bottom:.25rem; pointer-events:none; }
            .pc-thumb video { display:block; width:100%; height:2.6rem; object-fit:cover; border-radius:.3rem; }
            .pc-thumb-badge { position:absolute; inset:0; display:grid; place-items:center; }
            .pc-thumb-badge svg { width:.8rem; height:.8rem; color:#fff; background:rgb(0 0 0 / .55); border-radius:999px; padding:.3rem; box-sizing:content-box; }
            .pc-card .meta { display:flex; align-items:center; gap:.3rem; font-size:.68rem; color:#6b7280; }
            .dark .pc-card .meta { color:#a1a1aa; }
            .pc-card .meta .badge { border-radius:.3rem; padding:0 .3rem; background:rgb(0 0 0 / .06); }
            .dark .pc-card .meta .badge { background:rgb(255 255 255 / .1); }
            .pc-card .cap { font-size:.72rem; line-height:1.3; margin-top:.15rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
            .pc-more { font-size:.7rem; font-weight:600; color:#6b7280; padding:.15rem .2rem; background:none; border:0; cursor:pointer; text-align:left; border-radius:.3rem; }
            .pc-more:hover { color:#2d19ec; }

            .pc-note { border-radius:.5rem; padding:.4rem .45rem .3rem; margin-bottom:.3rem; }
            /* Drag & drop: notes + draft posts can be dragged onto another day. */
            .pc-note[draggable="true"], .pc-card.draft { cursor:grab; }
            .pc-note.dragging, .pc-card.dragging { opacity:.45; }
            .pc-day.drop { outline:2px dashed #2d19ec; outline-offset:-2px; background:rgb(45 25 236 / .04); }
            .dark .pc-day.drop { background:rgb(45 25 236 / .12); }
            .pc-note textarea { width:100%; border:0; background:transparent; resize:none; font-size:.74rem; line-height:1.35; color:#3f3f46; outline:none; min-height:2.2rem; overflow:hidden; }
            .pc-note-foot { display:flex; align-items:center; gap:.3rem; }
            .pc-note-foot .sw { width:.95rem; height:.95rem; border-radius:999px; border:1px solid rgb(0 0 0 / .15); cursor:pointer; flex:none; }
            .pc-note-foot input.tag { flex:1; min-width:0; border:0; background:transparent; font-size:.68rem; color:#52525b; outline:none; }
            .pc-note-foot .del { flex:none; border:0; background:transparent; cursor:pointer; padding:0; line-height:1; opacity:.55; }
            .pc-note-foot .del:hover { opacity:1; }
            .pc-pal { position:absolute; z-index:30; margin-top:.3rem; display:grid; grid-template-columns:repeat(3, 1.35rem); gap:.35rem; background:#fff; border:1px solid rgb(0 0 0 / .1); border-radius:.6rem; padding:.5rem; box-shadow:0 8px 24px rgb(0 0 0 / .14); }
            .dark .pc-pal { background:#232326; border-color:rgb(255 255 255 / .12); }
            .pc-pal button { width:1.35rem; height:1.35rem; border-radius:999px; border:1px solid rgb(0 0 0 / .12); cursor:pointer; }

            .pc-pop { position:absolute; right:0; z-index:40; margin-top:.4rem; width:19rem; background:#fff; border:1px solid rgb(0 0 0 / .1); border-radius:.75rem; box-shadow:0 10px 32px rgb(0 0 0 / .16); padding:.35rem; }
            .dark .pc-pop { background:#232326; border-color:rgb(255 255 255 / .12); }
            .pc-pop .head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding:.5rem .6rem .1rem; }
            .pc-pop .head b { font-size:.9rem; white-space:nowrap; }
            .pc-pop .sub { padding:0 .6rem .45rem; color:#6b7280; font-size:.72rem; }
            .pc-pop .row { display:flex; align-items:center; gap:.55rem; padding:.45rem .6rem; border-radius:.5rem; font-size:.85rem; }
            .pc-pop .row:hover { background:rgb(0 0 0 / .04); }
            .dark .pc-pop .row:hover { background:rgb(255 255 255 / .06); }
            .pc-pop .row .dot { width:.7rem; height:.7rem; border-radius:.2rem; flex:none; }
            .pc-pop .row .nm { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
            .pc-pop .foot { display:flex; align-items:center; gap:.4rem; width:100%; padding:.55rem .6rem; border-top:1px solid rgb(0 0 0 / .07); margin-top:.25rem; background:transparent; border-radius:0; font-size:.85rem; color:inherit; cursor:pointer; border-left:0; border-right:0; border-bottom:0; }
            .dark .pc-pop .foot { border-top-color:rgb(255 255 255 / .1); }
            .pc-pop .foot:hover { color:#2d19ec; }
            .pc-iconbtn { border:0; background:transparent; cursor:pointer; padding:.15rem; line-height:1; opacity:.6; }
            .pc-iconbtn:hover { opacity:1; }

            @media (max-width: 900px) { .pc-day { min-height:4.5rem; } .pc-card img { display:none; } }

            /* Post-type picker: a compact row of cards (icon on top, label
               beneath). Flat: no shadow, no hover motion; the selected state is
               a brand-tinted card. */
            .post-type-picker.fi-fo-toggle-buttons { display:flex; flex-wrap:wrap; gap:.5rem; }
            .post-type-picker .fi-fo-toggle-buttons-btn-ctn { flex:1 1 0; min-width:6rem; }
            .post-type-picker .fi-fo-toggle-buttons-btn-ctn .fi-btn {
                width:100%; flex-direction:column; gap:.4rem; padding:.65rem .5rem;
                border:1px solid rgb(17 24 39 / .1) !important;
                background:#fff !important; color:rgb(75 85 99) !important;
                border-radius:.75rem; font-weight:600; font-size:.85rem;
                box-shadow:none !important;
                transition:border-color .12s ease, background-color .12s ease, color .12s ease;
            }
            .post-type-picker .fi-fo-toggle-buttons-btn-ctn .fi-btn:hover { border-color:rgb(17 24 39 / .22) !important; }
            .post-type-picker .fi-fo-toggle-buttons-btn-ctn .fi-btn .fi-btn-icon { width:1.4rem; height:1.4rem; }
            /* Selected card: brand tint + border, no shadow. */
            .post-type-picker .fi-fo-toggle-buttons-btn-ctn:has(.fi-fo-toggle-buttons-input:checked) .fi-btn {
                border-color:#2d19ec !important; color:#2d19ec !important;
                background:rgb(45 25 236 / .06) !important;
            }
            .dark .post-type-picker .fi-fo-toggle-buttons-btn-ctn .fi-btn {
                border-color:rgb(255 255 255 / .12) !important;
                background:rgb(255 255 255 / .03) !important; color:#a1a1aa !important;
            }
            .dark .post-type-picker .fi-fo-toggle-buttons-btn-ctn .fi-btn:hover { border-color:rgb(255 255 255 / .25) !important; }
            .dark .post-type-picker .fi-fo-toggle-buttons-btn-ctn:has(.fi-fo-toggle-buttons-input:checked) .fi-btn {
                border-color:#a5b4fc !important; color:#c7d2fe !important;
                background:rgb(99 102 241 / .16) !important;
            }
        </style>

        @php
            $noteColors = \App\Models\PostNote::COLORS;
            $calendars = $this->externalCalendars();
            $postLabelMap = \App\Models\PostLabel::query()->orderBy('name')->get()->keyBy('id');
        @endphp

        <div class="pc-toolbar">
            @if ($this->mode === 'calendar')
                <div class="pc-nav">
                    <div class="pc-toggle" role="tablist">
                        <button type="button" class="{{ $this->calView === 'month' ? 'active' : '' }}" wire:click="setCalView('month')">{{ __('pages/posts.view_month') }}</button>
                        <button type="button" class="{{ $this->calView === 'week' ? 'active' : '' }}" wire:click="setCalView('week')">{{ __('pages/posts.view_week') }}</button>
                    </div>
                    <button type="button" class="pc-btn" wire:click="prevPeriod" aria-label="prev">‹</button>
                    <div class="pc-month">{{ $this->calendarLabel() }}</div>
                    <button type="button" class="pc-btn" wire:click="nextPeriod" aria-label="next">›</button>
                    <button type="button" class="pc-btn" wire:click="goToToday">{{ __('pages/posts.today') }}</button>
                </div>
            @endif

            <div style="display:flex; align-items:center; gap:.5rem; margin-left:auto;">
                {{-- Labels live in each post's dialog (header button + popover);
                     no page-level manager needed. --}}

                {{-- One Filter button, every dimension inside: locations, type,
                     status, labels, author, and (calendar) note tags. --}}
                @php
                    $fbLocations = $this->locationOptions();
                    $fbTags = $this->mode === 'calendar' ? $this->noteTags() : [];
                    $fbAuthors = $this->authorOptions();
                    $fbLabels = $this->labelFilterOptions();
                    $fbGroups = [
                        ['key' => 'filterTypes', 'title' => __('pages/posts.col_type'), 'selected' => array_map('strval', $this->filterTypes), 'options' => collect(['update', 'offer', 'event'])->mapWithKeys(fn ($t) => [$t => __('pages/posts.type_'.$t)])->all()],
                        ['key' => 'filterMedia', 'title' => __('pages/posts.filter_media'), 'selected' => array_map('strval', $this->filterMedia), 'options' => ['photo' => __('pages/posts.filter_media_photo'), 'video' => __('pages/posts.filter_media_video')]],
                        ['key' => 'filterStatuses', 'title' => __('pages/posts.col_status'), 'selected' => array_map('strval', $this->filterStatuses), 'options' => collect(['draft', 'scheduled', 'in_progress', 'published', 'failed'])->mapWithKeys(fn ($s) => [$s => __('pages/posts.status_'.$s)])->all()],
                    ];
                    if ($fbLabels !== []) {
                        $fbGroups[] = ['key' => 'filterLabels', 'title' => __('pages/posts.field_labels'), 'selected' => array_map('strval', $this->filterLabels), 'options' => $fbLabels];
                    }
                    if ($fbAuthors !== []) {
                        $fbGroups[] = ['key' => 'filterAuthors', 'title' => __('pages/posts.filter_author'), 'selected' => array_map('strval', $this->filterAuthors), 'options' => array_combine($fbAuthors, $fbAuthors)];
                    }
                @endphp
                @php
                    // Per-option counts, Planable-style, from one slim query.
                    $fpAll = \App\Models\Post::query()->get(['type', 'status', 'created_by_name', 'location_ids', 'label_ids', 'image_url', 'video_url']);
                    $fpTypeCounts = $fpAll->countBy('type');
                    $fpStatusCounts = $fpAll->countBy('status');
                    $fpAuthorCounts = $fpAll->countBy('created_by_name');
                    $fpLocCounts = fn (int $id): int => $fpAll->filter(fn ($p) => in_array($id, array_map('intval', $p->location_ids ?? []), true))->count();
                    $fpLabelCounts = fn (int $id): int => $fpAll->filter(fn ($p) => in_array($id, array_map('intval', $p->label_ids ?? []), true))->count();
                    $fpTagCounts = \App\Models\PostNote::query()->get(['tag'])->countBy(fn ($n) => $n->tag ?? '');

                    $fpSections = [];
                    if (count($fbLocations) > 1) {
                        $fpSections[] = ['id' => 'locations', 'icon' => 'heroicon-o-map-pin', 'title' => __('pages/posts.field_locations'), 'mode' => 'locations',
                            'active' => count($this->hiddenLocations),
                            'options' => collect($fbLocations)->map(fn ($name, $id) => ['value' => (int) $id, 'label' => $name, 'count' => $fpLocCounts((int) $id), 'checked' => ! in_array((int) $id, $this->hiddenLocations, true)])->values()->all()];
                    }
                    $fpMediaPhoto = $fpAll->filter(fn ($p) => filled($p->image_url))->count();
                    $fpMediaVideo = $fpAll->filter(fn ($p) => filled($p->video_url))->count();
                    $sectionIcons = ['filterTypes' => 'heroicon-o-rectangle-stack', 'filterMedia' => 'heroicon-o-photo', 'filterStatuses' => 'heroicon-o-clock', 'filterLabels' => 'heroicon-o-tag', 'filterAuthors' => 'heroicon-o-user'];
                    foreach ($fbGroups as $group) {
                        $counts = match ($group['key']) {
                            'filterTypes' => fn ($v) => (int) ($fpTypeCounts[$v] ?? 0),
                            'filterMedia' => fn ($v) => $v === 'photo' ? $fpMediaPhoto : $fpMediaVideo,
                            'filterStatuses' => fn ($v) => (int) ($fpStatusCounts[$v] ?? 0),
                            'filterAuthors' => fn ($v) => (int) ($fpAuthorCounts[$v] ?? 0),
                            'filterLabels' => fn ($v) => $fpLabelCounts((int) $v),
                        };
                        $fpSections[] = ['id' => $group['key'], 'icon' => $sectionIcons[$group['key']], 'title' => $group['title'], 'mode' => 'array', 'key' => $group['key'],
                            'active' => count($group['selected']),
                            'options' => collect($group['options'])->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label, 'count' => $counts((string) $value), 'checked' => in_array((string) $value, $group['selected'], true)])->values()->all()];
                    }
                    if ($fbTags !== []) {
                        $fpSections[] = ['id' => 'tags', 'icon' => 'heroicon-o-hashtag', 'title' => __('pages/posts.notes_filter_title'), 'mode' => 'tags',
                            'active' => count($this->hiddenNoteTags),
                            'options' => collect($fbTags)->map(fn ($tag) => ['value' => $tag, 'label' => '# '.$tag, 'count' => (int) ($fpTagCounts[$tag] ?? 0), 'checked' => ! in_array($tag, $this->hiddenNoteTags, true)])->values()->all()];
                    }
                @endphp
                <div x-data="{ open: false, sec: null }" style="position:relative;">
                    <button type="button" class="pc-btn" @click="open = !open" style="display:inline-flex; align-items:center; gap:.4rem;">
                        @svg('heroicon-o-funnel', ['style' => 'width:1rem; height:1rem; opacity:.7;'])
                        {{ __('pages/posts.filter') }}
                        @if ($this->activeFilterCount() > 0)
                            <span class="pc-fcount">{{ $this->activeFilterCount() }}</span>
                        @endif
                    </button>

                    <div class="pc-pop pc-pop-filter" x-show="open" x-cloak @click.outside="open = false">
                        <div class="pcf-title">
                            <b>{{ __('pages/posts.filter') }}</b>
                            <button type="button" class="pc-iconbtn" @click="open = false">@svg('heroicon-o-x-mark', ['style' => 'width:1rem; height:1rem;'])</button>
                        </div>
                        <div class="pcf-cap">{{ __('pages/posts.filter_by') }}</div>

                        @foreach ($fpSections as $section)
                            <div class="pcf-sec" :class="sec === '{{ $section['id'] }}' ? 'open' : ''">
                                <button type="button" class="pcf-head" @click="sec = sec === '{{ $section['id'] }}' ? null : '{{ $section['id'] }}'">
                                    @svg($section['icon'], ['style' => 'width:1rem; height:1rem; flex:none;'])
                                    <span>{{ $section['title'] }}</span>
                                    @if ($section['active'] > 0)<span class="pc-fcount">{{ $section['active'] }}</span>@endif
                                    <span class="pcf-chev" :style="sec === '{{ $section['id'] }}' ? 'transform: rotate(180deg)' : ''">@svg('heroicon-o-chevron-down')</span>
                                </button>
                                <div class="pcf-body" x-show="sec === '{{ $section['id'] }}'" x-collapse>
                                    @foreach ($section['options'] as $option)
                                        <label class="pcf-row">
                                            <input type="checkbox" @checked($option['checked'])
                                                @if ($section['mode'] === 'locations') wire:click="toggleLocationFilter({{ (int) $option['value'] }})"
                                                @elseif ($section['mode'] === 'tags') wire:click="toggleNoteTagFilter(@js($option['value']))"
                                                @else wire:click="toggleArrayFilter('{{ $section['key'] }}', {{ \Illuminate\Support\Js::from((string) $option['value']) }})" @endif>
                                            <span class="nm">{{ $option['label'] }}</span>
                                            <span class="cnt">{{ $option['count'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if ($this->activeFilterCount() > 0)
                            <div class="pcf-foot">
                                <button type="button" wire:click="clearPostFilters">
                                    @svg('heroicon-o-arrow-uturn-left')
                                    {{ __('pages/posts.filter_clear') }}
                                    <span class="pc-fcount">{{ $this->activeFilterCount() }}</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($this->mode === 'calendar')
                    {{-- External calendars: direct add when none yet, else a popover --}}
                    @if ($calendars->isEmpty())
                        <button type="button" class="pc-btn" wire:click="mountAction('addCalendar')" style="display:inline-flex; align-items:center; gap:.35rem;">
                            <svg style="width:1rem; height:1rem; opacity:.7;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            {{ __('pages/posts.calendars_connect') }}
                        </button>
                    @else
                    <div x-data="{ open: false }" style="position:relative;">
                        <button type="button" class="pc-btn" @click="open = !open" style="display:inline-flex; align-items:center; gap:.4rem;">
                            <svg style="width:1rem; height:1rem; opacity:.7;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                            @foreach ($calendars->take(3) as $calendar)
                                <span style="display:inline-block; width:.6rem; height:.6rem; border-radius:.2rem; background:{{ $noteColors[$calendar->color][1] ?? '#16a34a' }};"></span>
                            @endforeach
                            {{ trans_choice('pages/posts.calendars_button', $calendars->count(), ['count' => $calendars->count()]) }}
                        </button>

                        <div class="pc-pop" x-show="open" x-cloak @click.outside="open = false">
                            <div class="head">
                                <b>{{ __('pages/posts.calendars_title') }}</b>
                                @if ($calendars->isNotEmpty())
                                    <button type="button" class="pc-iconbtn" wire:click="refreshCalendars" title="{{ __('pages/posts.calendars_refresh') }}">
                                        <svg style="width:1rem; height:1rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    </button>
                                @endif
                            </div>
                            @if ($calendars->whereNotNull('synced_at')->isNotEmpty())
                                <div class="sub">{{ __('pages/posts.calendars_synced_ago', ['ago' => $calendars->whereNotNull('synced_at')->min('synced_at')->diffForHumans()]) }}</div>
                            @endif

                            @forelse ($calendars as $calendar)
                                <div class="row">
                                    <input type="checkbox" @checked($calendar->enabled) wire:click="toggleCalendar({{ $calendar->id }})" style="cursor:pointer;">
                                    <span class="dot" style="background:{{ $noteColors[$calendar->color][1] ?? '#16a34a' }};"></span>
                                    <span class="nm" title="{{ $calendar->sync_error ?: $calendar->name }}">{{ $calendar->name }}@if ($calendar->sync_error) ⚠️ @endif</span>
                                    <button type="button" class="pc-iconbtn" wire:click="confirmDeleteCalendar({{ $calendar->id }})" @click="open = false" title="{{ __('pages/posts.calendar_delete') }}">
                                        <svg style="width:.95rem; height:.95rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            @empty
                                <div style="padding:.5rem .6rem; font-size:.8rem; color:#6b7280;">{{ __('pages/posts.calendars_empty') }}</div>
                            @endforelse

                            <button type="button" class="foot" wire:click="mountAction('addCalendar')" @click="open = false">
                                <span style="font-size:1rem; line-height:1;">+</span> {{ __('pages/posts.calendar_add') }}
                            </button>
                        </div>
                    </div>
                    @endif
                @endif

                <div class="pc-toggle" role="tablist">
                    <button type="button" class="{{ $this->mode === 'calendar' ? 'active' : '' }}" wire:click="setMode('calendar')">{{ __('pages/posts.view_calendar') }}</button>
                    <button type="button" class="{{ $this->mode === 'table' ? 'active' : '' }}" wire:click="setMode('table')">{{ __('pages/posts.view_list') }}</button>
                </div>
            </div>
        </div>


        @if ($this->mode === 'calendar')
            @php
                $weeks = $this->calendarWeeks();
                $statusColors = ['published' => '#16a34a', 'scheduled' => '#0ea5e9', 'failed' => '#dc2626', 'in_progress' => '#d97706', 'draft' => '#9ca3af'];
                $isWeekView = $this->calView === 'week';
                $dowDays = $isWeekView ? collect($weeks[0] ?? [])->pluck('date') : null;
                $dowStart = \Carbon\CarbonImmutable::now()->startOfWeek(\Carbon\CarbonImmutable::MONDAY);
                $postLimit = $isWeekView ? 20 : 3;
            @endphp

            <datalist id="pc-note-tags">
                @foreach ($this->noteTags() as $tag)
                    <option value="{{ $tag }}"></option>
                @endforeach
            </datalist>

            {{-- On phones the 7-column grid scrolls horizontally instead of
                 squeezing the day cells into unreadable slivers. --}}
            <div class="pc-scroll">
            <div class="pc-grid {{ $isWeekView ? 'week' : '' }}">
                @for ($i = 0; $i < 7; $i++)
                    {{-- With a Monday-start week, columns 6 and 7 are Sat/Sun. --}}
                    <div class="pc-dow {{ $i >= 5 ? 'weekend' : '' }}">
                        {{ ($isWeekView ? $dowDays[$i] : $dowStart->addDays($i))->translatedFormat('D') }}
                        @if ($isWeekView)
                            <span style="{{ $dowDays[$i]->isToday() ? 'color:#fff; background:#dc2626; border-radius:.35rem; padding:.05rem .35rem; font-weight:700;' : '' }}">{{ $dowDays[$i]->day }}</span>
                        @endif
                    </div>
                @endfor

                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        {{-- Drop target: notes and DRAFT posts can be dragged onto
                             another day (payload "note:{id}" / "draft:{id}"). --}}
                        <div class="pc-day {{ $day['inMonth'] ? '' : 'out' }} {{ $day['date']->isWeekend() ? 'weekend' : '' }}"
                            x-data="{ all: false }"
                            @dragover.prevent="$event.dataTransfer.dropEffect = 'move'; $el.classList.add('drop')"
                            @dragleave="if (! $el.contains($event.relatedTarget)) $el.classList.remove('drop')"
                            @drop.prevent="
                                $el.classList.remove('drop');
                                const [kind, id] = $event.dataTransfer.getData('text/plain').split(':');
                                if (kind === 'note') $wire.moveNote(+id, '{{ $day['date']->format('Y-m-d') }}');
                                if (kind === 'draft') $wire.moveDraft(+id, '{{ $day['date']->format('Y-m-d') }}');
                            ">
                            @unless ($isWeekView)
                                <div class="pc-daynum">
                                    @if ($day['isToday'])<span class="today">{{ $day['date']->day }}</span>@else{{ $day['date']->day }}@endif
                                </div>
                            @endunless

                            <div class="pc-add">
                                <button type="button" wire:click="addPostOn('{{ $day['date']->format('Y-m-d') }}')" style="display:inline-flex; align-items:center; gap:.3rem;">@svg('heroicon-o-pencil-square', ['style' => 'width:.85rem; height:.85rem;']) {{ __('pages/posts.add_post') }}</button>
                                <button type="button" wire:click="addNote('{{ $day['date']->format('Y-m-d') }}')" style="display:inline-flex; align-items:center; gap:.3rem;">@svg('heroicon-o-document-text', ['style' => 'width:.85rem; height:.85rem;']) {{ __('pages/posts.add_note') }}</button>
                            </div>

                            @foreach ($day['events'] as $event)
                                @php [$evtBg, $evtAccent] = $noteColors[$event->calendar->color ?? 'green'] ?? ['#dcfce7', '#16a34a']; @endphp
                                <div class="pc-evt" style="background:{{ $evtBg }}; color:{{ $evtAccent }};" title="{{ $event->title }}">
                                    <span class="dot" style="background:{{ $evtAccent }};"></span>
                                    <span class="t">{{ $event->title }}</span>
                                </div>
                            @endforeach

                            @foreach ($day['notes'] as $note)
                                @php [$noteBg, $noteAccent] = $noteColors[$note->color] ?? $noteColors['yellow']; @endphp
                                {{-- Draggable to another day; dragging is disabled
                                     while a field inside is focused so text
                                     selection keeps working. --}}
                                <div class="pc-note" style="background:{{ $noteBg }};" wire:key="note-{{ $note->id }}"
                                    x-data="{ pal: false, editing: false }"
                                    :draggable="! editing"
                                    @focusin="editing = true"
                                    @focusout="editing = false"
                                    @dragstart="$event.dataTransfer.setData('text/plain', 'note:{{ $note->id }}'); $event.dataTransfer.effectAllowed = 'move'; $el.classList.add('dragging')"
                                    @dragend="$el.classList.remove('dragging')">
                                    <textarea
                                        placeholder="{{ __('pages/posts.note_placeholder') }}"
                                        x-init="$el.style.height = $el.scrollHeight + 'px'"
                                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        @blur="$wire.updateNote({{ $note->id }}, 'body', $el.value)"
                                    >{{ $note->body }}</textarea>
                                    <div class="pc-note-foot">
                                        <div style="position:relative;">
                                            <button type="button" class="sw" style="background:{{ $noteAccent }};" @click="pal = !pal" title="{{ __('pages/posts.note_color') }}"></button>
                                            <div class="pc-pal" x-show="pal" x-cloak @click.outside="pal = false">
                                                @foreach ($noteColors as $key => [$bg, $accent])
                                                    <button type="button" style="background:{{ $accent }};" title="{{ __('pages/posts.color_'.$key) }}"
                                                        @click="pal = false; $wire.updateNote({{ $note->id }}, 'color', '{{ $key }}')"></button>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input class="tag" list="pc-note-tags" value="{{ $note->tag }}" placeholder="{{ __('pages/posts.note_tag') }}"
                                            maxlength="60" @change="$wire.updateNote({{ $note->id }}, 'tag', $el.value)">
                                        <button type="button" class="del" style="color:{{ $noteAccent }};"
                                            wire:click="confirmDeleteNote({{ $note->id }})" title="{{ __('pages/posts.note_delete') }}">
                                            <svg style="width:.9rem; height:.9rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($day['posts']->values() as $postIndex => $post)
                                <button type="button" class="pc-card {{ $post->status === 'draft' ? 'draft' : '' }}" style="border-left-color: {{ $statusColors[$post->status] ?? '#9ca3af' }};"
                                    wire:click="showPost({{ $post->id }})"
                                    {{-- Cards past the cell limit stay hidden until "+ N more". --}}
                                    @if ($postIndex >= $postLimit) x-show="all" x-cloak @endif
                                    @if ($post->status === 'draft')
                                        {{-- Only drafts move: everything else lives on Google already. --}}
                                        draggable="true"
                                        @dragstart="$event.dataTransfer.setData('text/plain', 'draft:{{ $post->id }}'); $event.dataTransfer.effectAllowed = 'move'; $el.classList.add('dragging')"
                                        @dragend="$el.classList.remove('dragging')"
                                    @endif>
                                    @if ($post->image_url)
                                        {{-- draggable=false: browsers natively drag <img>, which fakes
                                             a card drag on published posts (only drafts really move). --}}
                                        <img src="{{ $post->image_url }}" alt="" loading="lazy" draggable="false">
                                    @elseif ($post->video_url)
                                        {{-- First frame as the thumbnail (metadata preload only, never
                                             playable from the card) + a Planable-style video badge. --}}
                                        <span class="pc-thumb">
                                            <video src="{{ $post->video_url }}#t=0.1" preload="metadata" muted playsinline disablepictureinpicture tabindex="-1"></video>
                                            <span class="pc-thumb-badge">
                                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"/></svg>
                                            </span>
                                        </span>
                                    @endif
                                    <span class="meta">
                                        <span>{{ ($post->scheduled_at ?? $post->created_at)->format('H:i') }}</span>
                                        <span>· {{ __('pages/posts.type_'.$post->type) }}</span>
                                        @if ($post->status === 'draft')
                                            <span class="badge">{{ __('pages/posts.status_draft') }}</span>
                                        @endif
                                    </span>
                                    @if (filled($post->caption) || filled($post->title))
                                        <span class="cap">{{ \Illuminate\Support\Str::limit($post->title ?: $post->caption, 60) }}</span>
                                    @endif
                                    @if (! empty($post->label_ids))
                                        <span style="display:flex; flex-wrap:wrap; gap:.2rem; margin-top:.25rem;">
                                            @foreach ($post->label_ids as $lid)
                                                @php $lbl = $postLabelMap->get($lid); @endphp
                                                @if ($lbl)
                                                    @php [$lBg, $lAccent] = $noteColors[$lbl->color] ?? $noteColors['blue']; @endphp
                                                    <span style="font-size:.6rem; font-weight:700; letter-spacing:.02em; padding:.05rem .3rem; border-radius:.25rem; background:{{ $lBg }}; color:{{ $lAccent }};">{{ $lbl->name }}</span>
                                                @endif
                                            @endforeach
                                        </span>
                                    @endif
                                    @php $loc = $this->locationLabel($post); @endphp
                                    @if ($loc)
                                        <span class="loc" style="display:flex; align-items:center; gap:.2rem; margin-top:.15rem; font-size:.7rem; color:#6b7280; white-space:nowrap; overflow:hidden;">
                                            @svg('heroicon-o-map-pin', ['style' => 'width:.75rem; height:.75rem; flex:none;'])
                                            <span style="overflow:hidden; text-overflow:ellipsis;">{{ $loc }}</span>
                                        </span>
                                    @endif
                                </button>
                            @endforeach

                            @if ($day['posts']->count() > $postLimit)
                                <button type="button" class="pc-more" @click="all = ! all">
                                    <span x-show="! all">+ {{ __('pages/posts.more_posts', ['count' => $day['posts']->count() - $postLimit]) }}</span>
                                    <span x-show="all" x-cloak>{{ __('pages/posts.show_less') }}</span>
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
            </div>{{-- /pc-scroll --}}

            {{-- HasTable pages get their action modals from the table markup;
                 in calendar mode the table isn't rendered, so provide the
                 modals container ourselves (create + viewPost dialogs). --}}
            <x-filament-actions::modals />
        @else
            {{ $this->table }}
        @endif
    @endif

</x-filament-panels::page>
