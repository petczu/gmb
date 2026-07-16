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

            .pc-grid { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); border:1px solid rgb(0 0 0 / .08); border-radius:.75rem; overflow:hidden; background:#fff; }
            .dark .pc-grid { background:#18181b; border-color: rgb(255 255 255 / .1); }
            .pc-dow { padding:.5rem .6rem; font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; border-bottom:1px solid rgb(0 0 0 / .08); background: rgb(0 0 0 / .02); }
            .dark .pc-dow { border-color: rgb(255 255 255 / .1); background: rgb(255 255 255 / .03); color:#a1a1aa; }
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
            .pc-card img { width:100%; height:2.6rem; object-fit:cover; border-radius:.3rem; margin-bottom:.25rem; }
            .pc-card .meta { display:flex; align-items:center; gap:.3rem; font-size:.68rem; color:#6b7280; }
            .dark .pc-card .meta { color:#a1a1aa; }
            .pc-card .meta .badge { border-radius:.3rem; padding:0 .3rem; background:rgb(0 0 0 / .06); }
            .dark .pc-card .meta .badge { background:rgb(255 255 255 / .1); }
            .pc-card .cap { font-size:.72rem; line-height:1.3; margin-top:.15rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
            .pc-more { font-size:.7rem; color:#6b7280; padding-left:.2rem; }

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
        </style>

        @php
            $noteColors = \App\Models\PostNote::COLORS;
            $calendars = $this->externalCalendars();
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
                {{-- Unified Filter: locations (multi-select) + note tags --}}
                @php
                    $filterTags = $this->mode === 'calendar' ? $this->noteTags() : [];
                    $hasLocationFilter = count($this->locationOptions()) > 1;
                    $activeFilters = count($this->hiddenLocations) + count($this->hiddenNoteTags);
                @endphp
                @if ($hasLocationFilter || $filterTags !== [])
                    <div x-data="{ open: false }" style="position:relative;">
                        <button type="button" class="pc-btn" @click="open = !open" style="display:inline-flex; align-items:center; gap:.4rem;">
                            <svg style="width:1rem; height:1rem; opacity:.7;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.792 2.938A49.069 49.069 0 0 1 12 2.25c2.797 0 5.54.236 8.209.688a1.857 1.857 0 0 1 1.541 1.836v1.044a3 3 0 0 1-.879 2.121l-6.182 6.182a1.5 1.5 0 0 0-.439 1.061v2.927a3 3 0 0 1-1.658 2.684l-1.757.878A.75.75 0 0 1 9.75 21v-5.818a1.5 1.5 0 0 0-.44-1.06L3.13 7.938a3 3 0 0 1-.879-2.121V4.773c0-.897.64-1.683 1.542-1.835Z"/></svg>
                            {{ __('pages/posts.filter') }}
                            @if ($activeFilters > 0)
                                <span style="display:inline-flex; align-items:center; justify-content:center; min-width:1.05rem; height:1.05rem; border-radius:999px; background:#2d19ec; color:#fff; font-size:.65rem; font-weight:700;">{{ $activeFilters }}</span>
                            @endif
                        </button>

                        <div class="pc-pop" x-show="open" x-cloak @click.outside="open = false">
                            @if ($hasLocationFilter)
                                <div class="head"><b>{{ __('pages/posts.field_locations') }}</b></div>
                                @foreach ($this->locationOptions() as $id => $name)
                                    <label class="row" style="cursor:pointer;">
                                        <input type="checkbox" @checked(! in_array((int) $id, $this->hiddenLocations, true)) wire:click="toggleLocationFilter({{ (int) $id }})" style="cursor:pointer;">
                                        <span class="nm">{{ $name }}</span>
                                    </label>
                                @endforeach
                            @endif

                            @if ($filterTags !== [])
                                <div class="head" style="{{ $hasLocationFilter ? 'margin-top:.7rem;' : '' }}"><b>{{ __('pages/posts.notes_filter_title') }}</b></div>
                                @foreach ($filterTags as $tag)
                                    <label class="row" style="cursor:pointer;">
                                        <input type="checkbox" @checked(! in_array($tag, $this->hiddenNoteTags, true)) wire:click="toggleNoteTagFilter(@js($tag))" style="cursor:pointer;">
                                        <span class="nm"># {{ $tag }}</span>
                                    </label>
                                @endforeach
                                <label class="row" style="cursor:pointer;">
                                    <input type="checkbox" @checked(! in_array(\App\Filament\App\Pages\Posts::UNTAGGED, $this->hiddenNoteTags, true)) wire:click="toggleNoteTagFilter('{{ \App\Filament\App\Pages\Posts::UNTAGGED }}')" style="cursor:pointer;">
                                    <span class="nm" style="color:#6b7280;">{{ __('pages/posts.notes_filter_untagged') }}</span>
                                </label>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($this->mode === 'calendar')
                    {{-- External calendars: direct add when none yet, else a popover --}}
                    @if ($calendars->isEmpty())
                        <button type="button" class="pc-btn" wire:click="mountAction('addCalendar')" style="display:inline-flex; align-items:center; gap:.35rem;">
                            <span style="font-size:1rem; line-height:1;">+</span> {{ __('pages/posts.calendars_connect') }}
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

            <div class="pc-grid {{ $isWeekView ? 'week' : '' }}">
                @for ($i = 0; $i < 7; $i++)
                    <div class="pc-dow">
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
                        <div class="pc-day {{ $day['inMonth'] ? '' : 'out' }}"
                            x-data
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
                                <button type="button" wire:click="addPostOn('{{ $day['date']->format('Y-m-d') }}')">✎ {{ __('pages/posts.add_post') }}</button>
                                <button type="button" wire:click="addNote('{{ $day['date']->format('Y-m-d') }}')">🗒 {{ __('pages/posts.add_note') }}</button>
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

                            @foreach ($day['posts']->take($postLimit) as $post)
                                <button type="button" class="pc-card {{ $post->status === 'draft' ? 'draft' : '' }}" style="border-left-color: {{ $statusColors[$post->status] ?? '#9ca3af' }};"
                                    wire:click="showPost({{ $post->id }})"
                                    @if ($post->status === 'draft')
                                        {{-- Only drafts move: everything else lives on Google already. --}}
                                        draggable="true"
                                        @dragstart="$event.dataTransfer.setData('text/plain', 'draft:{{ $post->id }}'); $event.dataTransfer.effectAllowed = 'move'; $el.classList.add('dragging')"
                                        @dragend="$el.classList.remove('dragging')"
                                    @endif>
                                    @if ($post->image_url)
                                        <img src="{{ $post->image_url }}" alt="" loading="lazy">
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
                                    @php $loc = $this->locationLabel($post); @endphp
                                    @if ($loc)
                                        <span class="loc" style="display:block; margin-top:.15rem; font-size:.7rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">📍 {{ $loc }}</span>
                                    @endif
                                </button>
                            @endforeach

                            @if ($day['posts']->count() > $postLimit)
                                <div class="pc-more">+ {{ $day['posts']->count() - $postLimit }}</div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- HasTable pages get their action modals from the table markup;
                 in calendar mode the table isn't rendered, so provide the
                 modals container ourselves (create + viewPost dialogs). --}}
            <x-filament-actions::modals />
        @else
            {{ $this->table }}
        @endif
    @endif
</x-filament-panels::page>
