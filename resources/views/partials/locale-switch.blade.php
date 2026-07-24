{{-- Language switcher: a compact "current language" button that opens a centered
     modal listing every supported language. Pure CSS (a hidden checkbox toggle)
     so it works on Filament auth pages and standalone pages alike, with no
     Alpine/JS. Pass ['dark' => true] on dark backgrounds. --}}
@php
    $dark = $dark ?? false;
    $current = app()->getLocale();
    $options = \App\Support\Locales::options();
    $currentCode = strtoupper(str_replace('_', '-', $current));
    // Unique id so the partial can be included more than once per page.
    $id = 'rw-lang-'.\Illuminate\Support\Str::random(5);
    $btn = $dark
        ? 'background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); color:#e9e6f7;'
        : 'background:#fff; border:1px solid #e5e7eb; color:#374151;';
@endphp
<div class="rw-lang" style="display:inline-block;">
    <style>
        #{{ $id }} { position:absolute; opacity:0; pointer-events:none; }
        .rw-lang label.rw-lang-btn { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .7rem; border-radius:999px; font-size:.8rem; font-weight:600; cursor:pointer; user-select:none; {!! $btn !!} }
        .rw-lang label.rw-lang-btn svg { width:1rem; height:1rem; opacity:.8; }
        .rw-lang .rw-lang-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:2147483646; }
        .rw-lang .rw-lang-modal { display:none; position:fixed; inset:0; z-index:2147483647; align-items:center; justify-content:center; padding:1rem; }
        #{{ $id }}:checked ~ .rw-lang-backdrop { display:block; }
        #{{ $id }}:checked ~ .rw-lang-modal { display:flex; }
        .rw-lang .rw-lang-card { width:100%; max-width:24rem; background:#fff; border-radius:1rem; box-shadow:0 24px 60px -12px rgba(0,0,0,.4); overflow:hidden; }
        .rw-lang .rw-lang-head { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f1f1f4; font-weight:700; color:#111827; }
        .rw-lang .rw-lang-close { cursor:pointer; color:#9ca3af; font-size:1.35rem; line-height:1; padding:.1rem .4rem; border-radius:.5rem; }
        .rw-lang .rw-lang-close:hover { background:#f4f4f6; color:#374151; }
        .rw-lang .rw-lang-list { display:grid; grid-template-columns:1fr 1fr; gap:.35rem; padding:1rem 1.25rem 1.25rem; max-height:60vh; overflow:auto; }
        .rw-lang .rw-lang-opt { display:flex; align-items:center; gap:.5rem; padding:.6rem .75rem; border-radius:.6rem; text-decoration:none; font-size:.9rem; color:#374151; border:1px solid transparent; }
        .rw-lang .rw-lang-opt:hover { background:#f4f4f6; }
        .rw-lang .rw-lang-opt.is-current { background:#eef0ff; color:#2d19ec; font-weight:600; border-color:#dcdcff; }
        .rw-lang .rw-lang-opt .rw-lang-code { margin-left:auto; font-size:.68rem; font-weight:600; color:#9ca3af; letter-spacing:.03em; }
        .rw-lang .rw-lang-opt.is-current .rw-lang-code { color:#2d19ec; }
    </style>

    <input type="checkbox" id="{{ $id }}">

    <label class="rw-lang-btn" for="{{ $id }}" aria-label="{{ __('common.language') }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.95 8.95 0 0 0 3.6-.75M12 21a8.95 8.95 0 0 1-3.6-.75M3.75 9h16.5M3.75 15h16.5M12 3a13 13 0 0 1 0 18M12 3a13 13 0 0 0 0 18"/></svg>
        {{ $currentCode }}
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width:.8rem; height:.8rem; opacity:.6;"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
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
                        <span>{{ $name }}</span>
                        <span class="rw-lang-code">{{ strtoupper(str_replace('_', '-', $code)) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
