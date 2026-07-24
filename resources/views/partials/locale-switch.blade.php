{{-- Language switcher: a compact "current language" button (flag in a circle)
     that opens a centered modal listing every supported language with its flag
     and native name. Pure CSS (a hidden checkbox toggle) so it works on Filament
     auth pages and standalone pages alike, no Alpine/JS. Pass ['dark' => true]
     on dark backgrounds. --}}
@php
    $dark = $dark ?? false;
    $current = app()->getLocale();
    $options = \App\Support\Locales::options();
    $flags = [
        'en' => '🇬🇧', 'de' => '🇩🇪', 'es' => '🇪🇸', 'fr' => '🇫🇷', 'it' => '🇮🇹',
        'nl' => '🇳🇱', 'pt_BR' => '🇧🇷', 'pl' => '🇵🇱', 'ja' => '🇯🇵', 'tr' => '🇹🇷', 'ar' => '🇸🇦',
    ];
    $flag = fn (string $c): string => $flags[$c] ?? '🌐';
    // Unique id so the partial can be included more than once per page.
    $id = 'rw-lang-'.\Illuminate\Support\Str::random(5);
    $btn = $dark
        ? 'background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); color:#e9e6f7;'
        : 'background:#fff; border:1px solid #e5e7eb; color:#374151;';
@endphp
<div class="rw-lang" style="display:inline-block;">
    <style>
        #{{ $id }} { position:absolute; opacity:0; pointer-events:none; }
        .rw-lang label.rw-lang-btn { display:inline-flex; align-items:center; gap:.45rem; padding:.3rem .55rem .3rem .35rem; border-radius:999px; cursor:pointer; user-select:none; {!! $btn !!} }
        .rw-lang .rw-flag { display:inline-flex; align-items:center; justify-content:center; width:1.7rem; height:1.7rem; border-radius:999px; background:{{ $dark ? 'rgba(255,255,255,.1)' : '#f3f4f6' }}; font-size:1rem; line-height:1; overflow:hidden; flex:0 0 auto; }
        .rw-lang label.rw-lang-btn svg { width:.8rem; height:.8rem; opacity:.55; margin-right:.15rem; }
        .rw-lang .rw-lang-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:2147483646; }
        .rw-lang .rw-lang-modal { display:none; position:fixed; inset:0; z-index:2147483647; align-items:center; justify-content:center; padding:1rem; }
        #{{ $id }}:checked ~ .rw-lang-backdrop { display:block; }
        #{{ $id }}:checked ~ .rw-lang-modal { display:flex; }
        .rw-lang .rw-lang-card { width:100%; max-width:26rem; background:#fff; border-radius:1rem; box-shadow:0 24px 60px -12px rgba(0,0,0,.4); overflow:hidden; }
        .rw-lang .rw-lang-head { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f1f1f4; font-weight:700; color:#111827; }
        .rw-lang .rw-lang-close { cursor:pointer; color:#9ca3af; font-size:1.35rem; line-height:1; padding:.1rem .4rem; border-radius:.5rem; }
        .rw-lang .rw-lang-close:hover { background:#f4f4f6; color:#374151; }
        .rw-lang .rw-lang-list { display:grid; grid-template-columns:1fr 1fr; gap:.25rem; padding:1rem 1rem 1.25rem; max-height:62vh; overflow:auto; }
        .rw-lang .rw-lang-opt { display:flex; align-items:center; gap:.6rem; padding:.55rem .6rem; border-radius:.7rem; text-decoration:none; font-size:.92rem; color:#374151; border:1px solid transparent; }
        .rw-lang .rw-lang-opt:hover { background:#f4f4f6; }
        .rw-lang .rw-lang-opt.is-current { background:#eef0ff; color:#2d19ec; font-weight:600; border-color:#dcdcff; }
        .rw-lang .rw-lang-opt .rw-flag { width:2rem; height:2rem; font-size:1.15rem; }
    </style>

    <input type="checkbox" id="{{ $id }}">

    <label class="rw-lang-btn" for="{{ $id }}" aria-label="{{ __('common.language') }}" title="{{ $options[$current] ?? '' }}">
        <span class="rw-flag">{{ $flag($current) }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </label>

    <label class="rw-lang-backdrop" for="{{ $id }}" aria-hidden="true"></label>

    <div class="rw-lang-modal" role="dialog" aria-modal="true">
        <div class="rw-lang-card" dir="ltr">
            <div class="rw-lang-head">
                <span>{{ __('common.select_language') }}</span>
                <label class="rw-lang-close" for="{{ $id }}" aria-label="{{ __('common.close') }}">&times;</label>
            </div>
            <div class="rw-lang-list">
                @foreach ($options as $code => $name)
                    <a href="{{ route('locale.switch', $code) }}"
                       class="rw-lang-opt {{ $code === $current ? 'is-current' : '' }}"
                       dir="{{ \App\Support\Locales::isRtl($code) ? 'rtl' : 'ltr' }}">
                        <span class="rw-flag">{{ $flag($code) }}</span>
                        <span>{{ $name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
