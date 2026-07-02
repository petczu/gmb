@php
    $current = app()->getLocale();
    $locales = ['en' => ['🇬🇧', 'EN'], 'de' => ['🇩🇪', 'DE']];
@endphp
<div style="display:flex; justify-content:center; align-items:center; gap:.5rem; font-size:.82rem;">
    @foreach ($locales as $code => [$flag, $label])
        @if ($code === $current)
            <span style="display:inline-flex; align-items:center; gap:.3rem; font-weight:700; color:#111827;">
                <span style="font-size:1rem; line-height:1;">{{ $flag }}</span>{{ $label }}
            </span>
        @else
            <a href="{{ route('locale.switch', $code) }}"
               style="display:inline-flex; align-items:center; gap:.3rem; color:#6b7280; text-decoration:none; opacity:.7;">
                <span style="font-size:1rem; line-height:1;">{{ $flag }}</span>{{ $label }}
            </a>
        @endif
        @if (! $loop->last)<span style="color:#d1d5db;">·</span>@endif
    @endforeach
</div>
