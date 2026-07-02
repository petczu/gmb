@props(['theme' => 'light'])
@php
    // Full wordmark when the sidebar is open / on desktop; icon-only when the
    // sidebar is collapsed or on mobile. Light/dark is chosen by Filament
    // (it renders this view once per theme and shows the matching one).
    // Accepts png/svg/webp so the file extension doesn't have to be exact.
    $resolve = function (string $base): ?string {
        foreach (['svg', 'png', 'webp'] as $ext) {
            if (is_file(public_path("logo/{$base}.{$ext}"))) {
                return asset("logo/{$base}.{$ext}");
            }
        }

        return null;
    };
    $full = $resolve('repunio-full-'.$theme);
    $icon = $resolve('repunio-icon-'.$theme);
    $textColor = $theme === 'dark' ? '#ffffff' : '#111827';
@endphp
@if ($full || $icon)
    <span style="display:inline-flex; align-items:center; height:100%;">
        <img src="{{ $full ?? $icon }}" alt="Repunio"
             style="height:100%; width:auto;"
             x-show="$store.sidebar?.isOpen ?? true">
        <img src="{{ $icon ?? $full }}" alt="Repunio"
             style="height:100%; width:auto; display:none;"
             x-show="!($store.sidebar?.isOpen ?? true)">
    </span>
@else
    {{-- Files not added yet (public/logo/repunio-*.png), show the name. --}}
    <span style="display:inline-flex; align-items:center; height:100%; font-weight:800; font-size:1.25rem; letter-spacing:-0.01em; color:{{ $textColor }};">Repunio</span>
@endif
