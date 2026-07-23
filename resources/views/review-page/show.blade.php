@php
    $s = $page->settings;
    $theme = ($s['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
    $accent = $s['accent'] ?? '#2d19ec';
    $bg = $theme === 'dark' ? '#0b0b0f' : '#f7f7f9';
    $fg = $theme === 'dark' ? '#ffffff' : '#111827';
    $muted = $theme === 'dark' ? 'rgba(255,255,255,.65)' : '#6b7280';

    $headline = $s['headline'][$lang] ?? $s['headline']['en'] ?? 'Leave a Review';
    $subtitle = $s['subtitle'][$lang] ?? $s['subtitle']['en'] ?? '';
    $logoUrl = $s['logo_url'] ?? null;

    // Per-platform button styling (brand colors); custom uses the accent.
    $buttonStyle = function (array $t) use ($accent, $theme): array {
        return match ($t['platform'] ?? 'custom') {
            'google' => ['bg' => '#ffffff', 'fg' => '#111827', 'border' => '#e5e7eb'],
            'tripadvisor' => ['bg' => '#34E0A1', 'fg' => '#111827', 'border' => '#34E0A1'],
            default => ['bg' => $accent, 'fg' => '#ffffff', 'border' => $accent],
        };
    };

    $labelFor = function (array $t) use ($lang): string {
        if (! empty($t['label'][$lang])) {
            return $t['label'][$lang];
        }
        if (! empty($t['label']['en'])) {
            return $t['label']['en'];
        }

        $names = ['google' => 'Google', 'tripadvisor' => 'TripAdvisor'];
        $platform = $names[$t['platform'] ?? ''] ?? '';

        return $lang === 'de'
            ? trim("Auf {$platform} bewerten")
            : trim("Review on {$platform}");
    };

    $iconFor = fn (array $t): string => match ($t['platform'] ?? 'custom') {
        'google' => '<svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.5 12.3c0-.9-.1-1.5-.3-2.2H12v4.1h6.5c-.1 1.1-.8 2.7-2.4 3.8l-.02.15 3.5 2.7.24.02c2.2-2 3.5-5 3.5-8.6z"/><path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.8-2.9c-1 .7-2.4 1.2-4.1 1.2-3.1 0-5.8-2.1-6.8-4.9l-.14.01-3.7 2.8-.05.13C3.3 21.3 7.3 24 12 24z"/><path fill="#FBBC05" d="M5.2 14.5c-.25-.7-.4-1.5-.4-2.5s.15-1.8.4-2.5l-.01-.16-3.7-2.9-.12.06C.5 8.1 0 10 0 12s.5 3.9 1.4 5.5l3.8-3z"/><path fill="#EB4335" d="M12 4.6c2.2 0 3.7 1 4.5 1.8l3.3-3.2C17.9 1.2 15.2 0 12 0 7.3 0 3.3 2.7 1.4 6.5l3.8 3c1-2.8 3.7-4.9 6.8-4.9z"/></svg>',
        'tripadvisor' => '<svg width="30" height="18" viewBox="0 0 506 300" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M87.3264 59.1917C134.775 53.0708 269.392 48.8543 232.511 243.376L274.344 239.973C256.019 120.069 294.348 58.9322 414.202 53.0708C215.419 -57.0063 102.242 57.2666 87.3264 59.1917Z" fill="#FAC415"/><path d="M272.383 212.572C294.185 271.56 359.677 301.708 418.668 279.91C477.658 258.112 507.81 192.622 486.017 133.63C464.223 74.6386 398.735 44.4815 339.742 66.2709C280.752 88.0818 250.6 153.572 272.383 212.572V212.572Z" fill="white"/><circle cx="126.755" cy="172.647" r="113.899" fill="white"/><circle cx="125.121" cy="171.531" r="21.0532" fill="#EE6946"/><circle cx="378.929" cy="171.531" r="21.044" fill="#00AF87"/><path fill-rule="evenodd" clip-rule="evenodd" d="M505.767 47.0016C494.586 62.3072 486.158 79.4441 480.861 97.6434C522.317 153.235 511.349 231.83 456.257 273.947C401.164 316.064 322.444 306.035 279.672 251.449L252.458 292.143L225.504 251.788C182.486 305.601 104.318 315.103 49.6604 273.163C-4.99768 231.224 -16.0528 153.261 24.7909 97.7792C19.4653 79.7486 11.0795 62.7678 0 47.5787L79.3113 47.5345C131.189 15.2545 191.336 -1.23631 252.423 0.0722513C312.048 -0.990664 370.699 15.2905 421.244 46.9366L505.767 47.0016ZM252.956 163.437C257.729 98.9239 310.503 48.4624 375.165 46.5839C336.443 29.8411 294.605 21.5137 252.423 22.1529C210.186 21.7431 168.303 29.8868 129.296 46.0935C194.602 47.4824 248.148 98.2936 252.956 163.437ZM126.758 273.724C85.8673 273.728 49.0007 249.098 33.3518 211.32C17.7029 173.541 26.3538 130.056 55.2702 101.144C84.1866 72.2316 127.673 63.5868 165.449 79.2411C203.225 94.8953 227.85 131.765 227.84 172.656C227.766 228.449 182.551 273.658 126.758 273.724ZM284.364 208.153C303.723 260.515 361.841 287.293 414.222 267.987C466.598 248.654 493.387 190.525 474.06 138.148C454.732 85.7701 396.606 58.9753 344.226 78.2977C291.847 97.62 265.046 155.744 284.364 208.125V208.153Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M67.3362 147.536C77.0466 124.163 99.8849 108.945 125.195 108.982C159.682 109.083 187.601 137.039 187.658 171.526C187.662 196.836 172.414 219.654 149.028 229.334C125.642 239.014 98.7276 233.648 80.8423 215.74C62.957 197.831 57.6258 170.91 67.3362 147.536ZM87.2488 187.284C93.6223 202.606 108.601 212.576 125.195 212.544C147.793 212.444 166.074 194.124 166.125 171.526C166.123 154.932 156.12 139.975 140.785 133.634C125.45 127.293 107.806 130.818 96.0843 142.564C84.3629 154.311 80.8753 171.963 87.2488 187.284Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M321.112 147.578C330.804 124.202 353.624 108.969 378.929 108.982C413.444 109.039 441.405 137.011 441.451 171.526C441.455 196.831 426.213 219.645 402.835 229.329C379.456 239.012 352.546 233.657 334.656 215.761C316.766 197.864 311.42 170.953 321.112 147.578ZM341.03 187.238C347.383 202.562 362.341 212.549 378.929 212.544C401.562 212.503 419.897 194.159 419.926 171.526C419.923 154.938 409.928 139.985 394.601 133.64C379.274 127.295 361.635 130.808 349.908 142.541C338.182 154.273 334.678 171.915 341.03 187.238Z" fill="black"/></svg>',
        default => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 4.5h-6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-6m-8-8h8m0 0v8m0-8L10 14"/></svg>',
    };

    $goUrl = fn (array $t): string => $page->custom_domain && request()->getHost() === $page->custom_domain
        ? '/go/'.($t['key'] ?? '')
        : route('review-page.go', ['slug' => $page->slug, 'target' => $t['key'] ?? '']);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ \App\Support\Locales::direction($lang) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $headline }}</title>
    <style>
        body { margin:0; min-height:100vh; background:{{ $bg }}; color:{{ $fg }}; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; display:flex; flex-direction:column; align-items:center; }
        .lang { align-self:flex-end; padding:1.1rem 1.4rem 0; font-size:.85rem; letter-spacing:.06em; }
        .lang a, .lang span { color:{{ $muted }}; text-decoration:none; }
        .lang .active { color:{{ $fg }}; font-weight:700; }
        main { width:100%; max-width:26rem; padding:1.5rem 1.25rem 3rem; box-sizing:border-box; text-align:center; }
        .logo img { max-height:88px; max-width:220px; object-fit:contain; }
        h1 { font-size:2rem; margin:1.2rem 0 .6rem; letter-spacing:.01em; }
        .sub { color:{{ $muted }}; font-size:.95rem; line-height:1.55; margin:0 0 1.8rem; }
        .btn { display:flex; align-items:center; justify-content:center; gap:.6rem; width:100%; box-sizing:border-box; padding:.95rem 1.2rem; border-radius:999px; font-size:1rem; font-weight:600; text-decoration:none; margin-bottom:1rem; box-shadow:0 8px 24px rgba(0,0,0,.18); }
    </style>
</head>
<body>
    @if (count($languages) > 1)
        <nav class="lang">
            @foreach ($languages as $i => $l)
                @if ($l === $lang)<span class="active">{{ strtoupper($l) }}</span>@elseif ($preview ?? false)<a href="#" onclick="parent.postMessage({previewLang: '{{ $l }}'}, '*'); return false;">{{ strtoupper($l) }}</a>@else<a href="?lang={{ $l }}">{{ strtoupper($l) }}</a>@endif
                @if (! $loop->last) <span>|</span> @endif
            @endforeach
        </nav>
    @endif

    <main>
        @if ($logoUrl)
            <div class="logo"><img src="{{ $logoUrl }}" alt=""></div>
        @endif

        <h1>{{ $headline }}</h1>
        @if ($subtitle !== '')
            <p class="sub">{{ $subtitle }}</p>
        @endif

        @foreach ($page->targets() as $t)
            @php $st = $buttonStyle($t); @endphp
            <a class="btn" href="{{ $goUrl($t) }}" rel="nofollow noopener"
               style="background:{{ $st['bg'] }}; color:{{ $st['fg'] }}; border:1px solid {{ $st['border'] }};">
                {!! $iconFor($t) !!} {{ $labelFor($t) }}
            </a>
        @endforeach
    </main>
</body>
</html>
