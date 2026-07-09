<x-filament-widgets::widget>
    <style>
        .perf-cols { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 900px) { .perf-cols { grid-template-columns: 1fr 1fr; } }
        .perf-split { display: flex; align-items: center; gap: 1.4rem; }
        .perf-donut { flex: none; }
        .perf-donut text { fill: currentColor; }
        .perf-legend { flex: 1 1 auto; min-width: 0; }
        .perf-row { display: flex; align-items: center; gap: .6rem; padding: .4rem 0; font-size: .85rem; }
        .perf-dot { width: .6rem; height: .6rem; border-radius: 999px; flex: none; }
        .perf-label { flex: 1 1 auto; min-width: 0; color: rgb(75 85 99); }
        .dark .perf-label { color: #a1a1aa; }
        .perf-val { font-weight: 600; white-space: nowrap; }
        .perf-pct { color: rgb(107 114 128); font-size: .78rem; white-space: nowrap; }
        .dark .perf-pct { color: #71717a; }
        .perf-kw { display: flex; align-items: baseline; gap: .6rem; padding: .38rem 0; font-size: .85rem; }
        .perf-kw + .perf-kw { border-top: 1px solid rgb(243 244 246); }
        .dark .perf-kw + .perf-kw { border-color: rgba(255,255,255,.08); }
        .perf-kw-rank { color: rgb(156 163 175); font-size: .75rem; width: 1.2rem; flex: none; }
        .perf-kw-term { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .perf-kw-hits { font-weight: 600; white-space: nowrap; }
        .perf-hint { font-size: .78rem; color: rgb(107 114 128); margin-top: .2rem; }
        .dark .perf-hint { color: #71717a; }
    </style>

    <div class="perf-cols">
        <x-filament::section>
            <x-slot name="heading">{{ __('widgets.perf_breakdown_title') }}</x-slot>

            <div class="perf-split">
                @php
                    $radius = 38;
                    $circumference = 2 * M_PI * $radius;
                    $donutOffset = 0.0;
                @endphp
                <svg class="perf-donut" viewBox="0 0 100 100" width="130" height="130" role="img">
                    <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="rgba(128,128,128,.15)" stroke-width="15"/>
                    @foreach ($breakdown as $row)
                        @php $len = max(0, min(100, $row['pct'])) / 100 * $circumference; @endphp
                        @if ($len > 0)
                            <circle cx="50" cy="50" r="{{ $radius }}" fill="none"
                                stroke="{{ $row['color'] }}" stroke-width="15"
                                stroke-dasharray="{{ round($len, 2) }} {{ round($circumference - $len, 2) }}"
                                stroke-dashoffset="{{ round(-$donutOffset, 2) }}"
                                transform="rotate(-90 50 50)"/>
                        @endif
                        @php $donutOffset += $len; @endphp
                    @endforeach
                    <text x="50" y="48" text-anchor="middle" font-size="15" font-weight="700">{{ $views >= 10000 ? round($views / 1000, 1).'K' : number_format($views) }}</text>
                    <text x="50" y="62" text-anchor="middle" font-size="7" opacity=".6">{{ __('widgets.perf_views') }}</text>
                </svg>

                <div class="perf-legend">
                    @foreach ($breakdown as $row)
                        <div class="perf-row">
                            <span class="perf-dot" style="background: {{ $row['color'] }}"></span>
                            <span class="perf-label">{{ $row['label'] }}</span>
                            <span class="perf-val">{{ number_format($row['value']) }}</span>
                            <span class="perf-pct">{{ $row['pct'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('widgets.perf_searches_title') }}</x-slot>

            @forelse ($keywords as $i => $row)
                <div class="perf-kw">
                    <span class="perf-kw-rank">{{ $i + 1 }}.</span>
                    <span class="perf-kw-term">{{ $row['keyword'] }}</span>
                    <span class="perf-kw-hits">{{ number_format($row['impressions']) }}</span>
                </div>
            @empty
                <div class="perf-hint">{{ __('widgets.perf_no_data') }}</div>
            @endforelse

            <div class="perf-hint" style="margin-top: .7rem;">{{ __('widgets.perf_searches_desc') }}</div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
