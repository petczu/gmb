<div x-data="{ open: false }"
     x-on:ask-ai-answer.window="$wire.answer()"
     x-on:ask-ai-scroll.window="$nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight })"
     style="position:fixed; right:1.4rem; bottom:1.4rem; z-index:40;">
    {{-- Single Livewire root: styles must live INSIDE it, never as a sibling. --}}
    <style>
        .ask-ai-md > :first-child { margin-top: 0; }
        .ask-ai-md > :last-child { margin-bottom: 0; }
        .ask-ai-md p { margin: .4rem 0; }
        .ask-ai-md h1, .ask-ai-md h2, .ask-ai-md h3 { font-size: .9rem; font-weight: 700; margin: .7rem 0 .35rem; }
        .ask-ai-md ul, .ask-ai-md ol { margin: .35rem 0; padding-left: 1.1rem; }
        .ask-ai-md li { margin: .12rem 0; }
        .ask-ai-md strong { font-weight: 700; }
        .ask-ai-md code { background: #e9e9ee; padding: .05rem .3rem; border-radius: .3rem; font-size: .8rem; }
        .ask-ai-md table { border-collapse: collapse; width: 100%; margin: .5rem 0; font-size: .78rem; display: block; overflow-x: auto; }
        .ask-ai-md th, .ask-ai-md td { border: 1px solid #e5e7eb; padding: .3rem .5rem; text-align: left; white-space: nowrap; }
        .ask-ai-md th { background: #ececf1; font-weight: 600; }
        .ask-ai-md tr:nth-child(even) td { background: #fafafb; }
        .ask-ai-md a { color: #2d19ec; text-decoration: underline; }
    </style>

    {{-- Backdrop: dims the app behind the slide-over (click to dismiss). --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-on:click="open = false"
         style="position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:41;"></div>

    {{-- Slide-over panel from the right, like the review editor. The outer
         element carries x-show + the slide transition; Alpine wipes the inline
         `display` when it toggles, so the grid layout lives on the inner element
         it never touches (otherwise the thread stops scrolling). --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         x-on:keydown.escape.window="open = false"
         x-effect="if (open) $nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight })"
         style="position:fixed; top:0; right:0; bottom:0; width:30rem; max-width:100vw; z-index:42;">
    <div style="height:100%; background:#fff; box-shadow:-24px 0 60px rgba(0,0,0,.18); display:grid; grid-template-rows:auto minmax(0,1fr) auto; overflow:hidden;">

        {{-- Top: header + optional history dropdown (grid row 1, auto height). --}}
        <div>
        {{-- Header --}}
        <div style="display:flex; align-items:center; gap:.6rem; padding:.85rem 1rem; border-bottom:1px solid #f3f4f6; background:#fafafa;">
            <span style="display:inline-flex; width:1.9rem; height:1.9rem; border-radius:999px; background:#2d19ec; color:#fff; align-items:center; justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.05rem; height:1.05rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.625 2.625 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd"/></svg>
            </span>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:.92rem; color:#111827;">{{ __('pages/ask_ai.title') }}</div>
                <div style="font-size:.72rem; color:#9ca3af;">{{ __('pages/ask_ai.subtitle') }}</div>
            </div>
            {{-- History --}}
            <button type="button" wire:click="toggleHistory" title="{{ __('pages/ask_ai.history') }}"
                    style="border:none; background:none; color:{{ $showHistory ? '#2d19ec' : '#9ca3af' }}; cursor:pointer; padding:.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1.15rem; height:1.15rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </button>
            {{-- New chat --}}
            <button type="button" wire:click="newChat" title="{{ __('pages/ask_ai.new_chat') }}"
                    style="border:none; background:none; color:#9ca3af; cursor:pointer; padding:.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1.2rem; height:1.2rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </button>
            <button type="button" x-on:click="open = false"
                    style="border:none; background:none; color:#9ca3af; cursor:pointer; padding:.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.15rem; height:1.15rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- History dropdown --}}
        @if ($showHistory)
            <div style="border-bottom:1px solid #f3f4f6; background:#fff; max-height:14rem; overflow-y:auto;">
                @forelse ($conversations as $conversation)
                    <div wire:key="conv-{{ $conversation->id }}"
                         style="display:flex; align-items:center; gap:.5rem; padding:.55rem 1rem; font-size:.82rem; cursor:pointer; {{ $conversation->id === $conversationId ? 'background:#f4f4ff;' : '' }}"
                         wire:click="openConversation({{ $conversation->id }})">
                        <span style="flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#111827;">{{ $conversation->title ?: __('pages/ask_ai.untitled') }}</span>
                        <span style="flex:none; color:#9ca3af; font-size:.7rem;">{{ $conversation->last_message_at?->diffForHumans(short: true) }}</span>
                        <button type="button" wire:click.stop="deleteConversation({{ $conversation->id }})" title="{{ __('pages/ask_ai.delete') }}"
                                style="flex:none; border:none; background:none; color:#c0c0c8; cursor:pointer; padding:.1rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:.95rem; height:.95rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @empty
                    <div style="padding:.7rem 1rem; font-size:.8rem; color:#9ca3af;">{{ __('pages/ask_ai.no_history') }}</div>
                @endforelse
            </div>
        @endif
        </div>{{-- /top --}}

        @php($noLocations = ! $this->hasLocations())

        {{-- Thread (grid row 2: minmax(0,1fr) makes it the only scrolling area). --}}
        <div x-ref="thread" style="min-height:0; overflow-y:auto; -webkit-overflow-scrolling:touch; padding:1rem; display:flex; flex-direction:column; gap:.6rem; background:#fff;">
            @if ($noLocations)
                {{-- No connected location: nothing to ask about yet. --}}
                <div style="border:1px solid #eef2f7; background:#f9fafb; border-radius:.8rem; padding:1rem; color:#6b7280; font-size:.85rem; line-height:1.55;">
                    <div style="font-weight:700; color:#111827; margin-bottom:.35rem;">{{ __('pages/ask_ai.no_location_title') }}</div>
                    {{ __('pages/ask_ai.no_location_body') }}
                    <a href="{{ url('/locations') }}" style="display:inline-flex; align-items:center; gap:.35rem; margin-top:.7rem; background:#2d19ec; color:#fff; border-radius:.6rem; padding:.45rem .8rem; font-size:.82rem; font-weight:600; text-decoration:none;">
                        {{ __('pages/ask_ai.no_location_cta') }}
                    </a>
                </div>
            @elseif ($messages === [])
                {{-- Bot introduction: a friendly face instead of a bare hint. --}}
                <div style="text-align:center; padding:1.6rem .5rem .3rem;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:4.2rem; height:4.2rem; border-radius:999px; background:linear-gradient(135deg, #2d19ec, #7c6cf9); box-shadow:0 8px 20px rgb(45 25 236 / .3);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" style="width:2.2rem; height:2.2rem;">
                            <circle cx="12" cy="4.2" r="1" fill="#fff"/>
                            <path stroke-linecap="round" d="M12 5.2v1.6"/>
                            <rect x="5" y="6.8" width="14" height="10.5" rx="3.4"/>
                            <circle cx="9.4" cy="11.4" r="1.15" fill="#fff" stroke="none"/>
                            <circle cx="14.6" cy="11.4" r="1.15" fill="#fff" stroke="none"/>
                            <path stroke-linecap="round" d="M9.8 14.6c.6.55 1.35.85 2.2.85s1.6-.3 2.2-.85"/>
                            <path stroke-linecap="round" d="M3.4 11v2.6M20.6 11v2.6"/>
                        </svg>
                    </span>
                    <div style="font-weight:700; font-size:1.05rem; color:#111827; margin:.8rem 0 .3rem;">{{ __('pages/ask_ai.empty_title') }}</div>
                    <div style="color:#6b7280; font-size:.85rem; line-height:1.55; max-width:24rem; margin:0 auto;">{{ __('pages/ask_ai.empty_body') }}</div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.5rem; justify-content:center;">
                    @foreach (__('pages/ask_ai.examples') as $example)
                        <button type="button" wire:click="ask({{ \Illuminate\Support\Js::from($example) }})"
                                style="border:1px solid #e5e7eb; background:#fff; border-radius:999px; padding:.35rem .7rem; font-size:.78rem; color:#374151; cursor:pointer; text-align:left;">
                            {{ $example }}
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($messages as $message)
                @if ($message['role'] === 'user')
                    <div style="align-self:flex-end; max-width:88%; background:#2d19ec; color:#fff; border-radius:.9rem .9rem .2rem .9rem; padding:.55rem .85rem; font-size:.86rem; white-space:pre-wrap;">{{ $message['content'] }}</div>
                @else
                    <div class="ask-ai-md" style="align-self:flex-start; max-width:88%; background:#f4f4f6; color:#111827; border-radius:.9rem .9rem .9rem .2rem; padding:.6rem .85rem; font-size:.86rem; line-height:1.55;">{!! \App\Support\ChatRenderer::render($message['content']) !!}</div>
                @endif
            @endforeach

            @if ($busy)
                <div style="align-self:flex-start; background:#f4f4f6; border-radius:.9rem; padding:.6rem .85rem; color:#9ca3af; font-size:.86rem;">
                    <span style="display:inline-block; width:.8rem; height:.8rem; border:2px solid #e5e7eb; border-top-color:#2d19ec; border-radius:50%; animation:aask .8s linear infinite; vertical-align:-2px; margin-right:.35rem;"></span>
                    {{ __('pages/ask_ai.thinking') }}
                </div>
                <style>@keyframes aask { to { transform: rotate(360deg); } }</style>
            @endif
        </div>

        {{-- Composer (disabled until a location is connected) --}}
        <form wire:submit="send" style="display:flex; gap:.5rem; padding:.75rem; border-top:1px solid #f3f4f6; background:#fafafa;">
            <input type="text" wire:model="question"
                   placeholder="{{ $noLocations ? __('pages/ask_ai.no_location_placeholder') : __('pages/ask_ai.placeholder') }}"
                   @disabled($busy || $noLocations)
                   style="flex:1; min-width:0; border:1px solid #e5e7eb; border-radius:.65rem; padding:.55rem .8rem; font-size:.86rem; background:{{ $noLocations ? '#f3f4f6' : '#fff' }};">
            <button type="submit" @disabled($busy || $noLocations)
                    style="border:none; background:{{ $noLocations ? '#c7c7d1' : '#2d19ec' }}; color:#fff; border-radius:.65rem; padding:.55rem .8rem; cursor:{{ $noLocations ? 'not-allowed' : 'pointer' }}; display:inline-flex; align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem; height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
            </button>
        </form>
    </div>{{-- /grid --}}
    </div>{{-- /window --}}

    {{-- Launcher --}}
    <button type="button" x-on:click="open = !open"
            title="{{ __('pages/ask_ai.title') }}"
            style="width:3.4rem; height:3.4rem; border-radius:999px; border:none; background:#2d19ec; color:#fff; cursor:pointer; box-shadow:0 10px 24px rgba(45,25,236,.35); display:flex; align-items:center; justify-content:center;">
        <template x-if="!open">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.5rem; height:1.5rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.625 2.625 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd"/></svg>
        </template>
        <template x-if="open">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.4rem; height:1.4rem;"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </template>
    </button>
</div>
