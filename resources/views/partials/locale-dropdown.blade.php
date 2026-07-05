{{-- Language dropdown (vanilla JS — works on Filament pages AND standalone
     pages without Alpine). Position it via a wrapper at the call site. --}}
@php
    $current = app()->getLocale();
    $locales = ['en' => ['🇬🇧', 'English'], 'de' => ['🇩🇪', 'Deutsch']];
@endphp
<div class="locale-dd" style="position:relative;">
    <button type="button"
            onclick="var m = this.nextElementSibling; m.style.display = m.style.display === 'block' ? 'none' : 'block';"
            style="display:inline-flex; align-items:center; gap:.45rem; background:#fff; border:1px solid #e5e7eb; border-radius:.55rem; padding:.4rem .75rem; font-size:.85rem; color:#374151; cursor:pointer;">
        <svg style="width:1rem; height:1rem; color:#6b7280;" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5 0 4.5-4 4.5-9S14.5 3 12 3 7.5 7 7.5 12s2 9 4.5 9zM3.6 9h16.8M3.6 15h16.8"/>
        </svg>
        {{ $locales[$current][1] ?? 'English' }}
        <svg style="width:.8rem; height:.8rem; color:#9ca3af;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div class="locale-dd-menu"
         style="display:none; position:absolute; right:0; margin-top:.35rem; min-width:9.5rem; background:#fff; border:1px solid #e5e7eb; border-radius:.6rem; box-shadow:0 10px 30px rgba(0,0,0,.08); padding:.3rem; z-index:30;">
        @foreach ($locales as $code => [$flag, $label])
            <a href="{{ route('locale.switch', $code) }}"
               style="display:flex; align-items:center; gap:.5rem; padding:.45rem .6rem; border-radius:.45rem; font-size:.85rem; text-decoration:none; color:{{ $code === $current ? '#1800ff' : '#374151' }}; font-weight:{{ $code === $current ? '600' : '400' }}; background:{{ $code === $current ? '#eef2ff' : 'transparent' }};">
                <span style="font-size:1rem; line-height:1;">{{ $flag }}</span>{{ $label }}
                @if ($code === $current)
                    <svg style="width:.9rem; height:.9rem; margin-left:auto;" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
<script>
    // Close any open locale menu when clicking outside of it (bound once).
    if (! window.__localeDdBound) {
        window.__localeDdBound = true;
        document.addEventListener('click', function (e) {
            document.querySelectorAll('.locale-dd').forEach(function (dd) {
                if (! dd.contains(e.target)) {
                    dd.querySelector('.locale-dd-menu').style.display = 'none';
                }
            });
        });
    }
</script>
