<div x-data="{ open: false }"
     x-on:ask-ai-answer.window="$wire.answer()"
     x-on:ask-ai-scroll.window="$nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight })"
     style="position:fixed; right:1.4rem; bottom:1.4rem; z-index:40;">

    {{-- Chat window --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition"
         x-on:keydown.escape.window="open = false"
         x-effect="if (open) $nextTick(() => { if ($refs.thread) $refs.thread.scrollTop = $refs.thread.scrollHeight })"
         style="position:absolute; right:0; bottom:4.4rem; width:24rem; max-width:calc(100vw - 2.8rem); height:31rem; max-height:calc(100vh - 8rem); background:#fff; border:1px solid #e5e7eb; border-radius:1rem; box-shadow:0 20px 50px rgba(0,0,0,.18); display:flex; flex-direction:column; overflow:hidden;">

        {{-- Header --}}
        <div style="display:flex; align-items:center; gap:.6rem; padding:.85rem 1rem; border-bottom:1px solid #f3f4f6; background:#fafafa;">
            <span style="display:inline-flex; width:1.9rem; height:1.9rem; border-radius:999px; background:#2d19ec; color:#fff; align-items:center; justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1.05rem; height:1.05rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258a2.625 2.625 0 0 0-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5Z" clip-rule="evenodd"/></svg>
            </span>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:700; font-size:.92rem; color:#111827;">{{ __('pages/ask_ai.title') }}</div>
                <div style="font-size:.72rem; color:#9ca3af;">{{ __('pages/ask_ai.subtitle') }}</div>
            </div>
            @if ($messages !== [])
                <button type="button" wire:click="clearChat" title="{{ __('pages/ask_ai.clear') }}"
                        style="border:none; background:none; color:#9ca3af; cursor:pointer; padding:.25rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width:1.1rem; height:1.1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </button>
            @endif
            <button type="button" x-on:click="open = false"
                    style="border:none; background:none; color:#9ca3af; cursor:pointer; padding:.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1.15rem; height:1.15rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Thread --}}
        <div x-ref="thread" style="flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:.6rem; background:#fff;">
            @if ($messages === [])
                <div style="color:#6b7280; font-size:.85rem; line-height:1.55;">
                    <div style="font-weight:700; color:#111827; margin-bottom:.3rem;">{{ __('pages/ask_ai.empty_title') }}</div>
                    {{ __('pages/ask_ai.empty_body') }}
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.4rem;">
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
                    <div style="align-self:flex-start; max-width:88%; background:#f4f4f6; color:#111827; border-radius:.9rem .9rem .9rem .2rem; padding:.6rem .85rem; font-size:.86rem; line-height:1.55; white-space:pre-wrap;">{{ $message['content'] }}</div>
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

        {{-- Composer --}}
        <form wire:submit="send" style="display:flex; gap:.5rem; padding:.75rem; border-top:1px solid #f3f4f6; background:#fafafa;">
            <input type="text" wire:model="question"
                   placeholder="{{ __('pages/ask_ai.placeholder') }}"
                   @disabled($busy)
                   style="flex:1; min-width:0; border:1px solid #e5e7eb; border-radius:.65rem; padding:.55rem .8rem; font-size:.86rem; background:#fff;">
            <button type="submit" @disabled($busy)
                    style="border:none; background:#2d19ec; color:#fff; border-radius:.65rem; padding:.55rem .8rem; cursor:pointer; display:inline-flex; align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem; height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
            </button>
        </form>
    </div>

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
