{{-- Compact EN/DE language pill. Vanilla markup (no Alpine) so it works on
     Filament auth pages and standalone pages alike. Pass ['dark' => true] on
     dark backgrounds (e.g. the beta pending screen). --}}
@php
    $dark = $dark ?? false;
    $current = app()->getLocale();
    $box = $dark
        ? 'background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16);'
        : 'background:#fff; border:1px solid #e5e7eb;';
    $idle = $dark ? '#b9b3d9' : '#6b7280';
@endphp
<div style="display:inline-flex; gap:.15rem; border-radius:999px; padding:.2rem; {{ $box }}">
    @foreach (['en' => 'EN', 'de' => 'DE'] as $code => $label)
        <a href="{{ route('locale.switch', $code) }}"
           style="display:inline-flex; align-items:center; padding:.3rem .7rem; border-radius:999px; font-size:.78rem; font-weight:600; text-decoration:none; transition:background-color .15s ease, color .15s ease; {{ $code === $current ? 'background:#2d19ec; color:#fff;' : 'color:'.$idle.';' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
