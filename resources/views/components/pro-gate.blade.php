{{-- Centered upsell card shown in place of a Pro-only page's content. --}}
@props([
    'title',
    'body',
    'cta',
    'icon' => 'lock',
])

<style>
    .pro-gate { position:relative; overflow:hidden; text-align:center; padding:4rem 1.5rem 3.5rem; border:1px solid rgb(0 0 0 / .08); border-radius:1rem; background:#fff; }
    .dark .pro-gate { background:#18181b; border-color: rgb(255 255 255 / .1); }
    .pro-gate::before { content:''; position:absolute; inset:-40% -20% auto; height:70%; background:radial-gradient(ellipse at top, rgb(45 25 236 / .07), transparent 65%); pointer-events:none; }
    .pro-gate .icon-ring { position:relative; display:inline-flex; align-items:center; justify-content:center; width:4.5rem; height:4.5rem; border-radius:999px; background:linear-gradient(135deg, #eef2ff, #e0e7ff); }
    .dark .pro-gate .icon-ring { background:linear-gradient(135deg, rgb(255 255 255 / .06), rgb(45 25 236 / .25)); }
    .pro-gate .icon-ring svg { width:2.1rem; height:2.1rem; color:#2d19ec; }
    .dark .pro-gate .icon-ring svg { color:#a5b4fc; }
    .pro-gate .pill { position:absolute; top:-.3rem; right:-.55rem; background:#2d19ec; color:#fff; font-size:.62rem; font-weight:800; letter-spacing:.08em; padding:.18rem .45rem; border-radius:999px; box-shadow:0 2px 6px rgb(45 25 236 / .4); }
    .pro-gate h2 { font-size:1.15rem; font-weight:700; margin:1.1rem 0 .45rem; }
    .pro-gate p { max-width:34rem; margin:0 auto 1.4rem; font-size:.92rem; line-height:1.6; color:#6b7280; }
    .dark .pro-gate p { color:#a1a1aa; }
</style>

<div class="pro-gate">
    <span class="icon-ring">
        <span class="pill">PRO</span>
        @switch($icon)
            @case('bolt')
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                @break
            @case('key')
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"/></svg>
                @break
            @case('sparkles')
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
                @break
            @default
                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        @endswitch
    </span>

    <h2>{{ $title }}</h2>
    <p>{{ $body }}</p>

    <x-filament::button tag="a" :href="\App\Filament\App\Pages\Billing::getUrl()" size="lg">
        {{ $cta }}
    </x-filament::button>
</div>
