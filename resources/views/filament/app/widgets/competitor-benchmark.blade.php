<x-filament-widgets::widget>
    <style>
        .cmp-row { display: flex; align-items: center; gap: 1rem; padding: .65rem 0; font-size: .85rem; }
        .cmp-row + .cmp-row { border-top: 1px solid rgb(243 244 246); }
        .dark .cmp-row + .cmp-row { border-color: rgba(255,255,255,.08); }
        .cmp-name { flex: 1 1 32%; min-width: 0; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .cmp-ratings { flex: 0 0 auto; display: flex; align-items: baseline; gap: .45rem; white-space: nowrap; }
        .cmp-you { font-weight: 700; }
        .cmp-vs { color: rgb(156 163 175); font-size: .75rem; }
        .cmp-chip { flex: 0 0 auto; font-size: .72rem; font-weight: 600; padding: .18rem .55rem; border-radius: 999px; white-space: nowrap; }
        .cmp-chip-up { background: rgb(220 252 231); color: rgb(22 101 52); }
        .dark .cmp-chip-up { background: rgba(34,197,94,.15); color: #4ade80; }
        .cmp-chip-down { background: rgb(254 226 226); color: rgb(153 27 27); }
        .dark .cmp-chip-down { background: rgba(239,68,68,.15); color: #f87171; }
        .cmp-chip-flat { background: rgb(0 0 0 / .06); color: rgb(107 114 128); }
        .dark .cmp-chip-flat { background: rgb(255 255 255 / .1); color: #a1a1aa; }
        .cmp-new { flex: 1 1 auto; text-align: right; color: rgb(107 114 128); font-size: .78rem; white-space: nowrap; }
        .dark .cmp-new { color: #a1a1aa; }
        .cmp-new strong { color: inherit; }
        .cmp-spark { flex: 0 0 auto; }
        .cmp-hint { font-size: .78rem; color: rgb(107 114 128); margin-top: .6rem; }
        .dark .cmp-hint { color: #71717a; }
        .cmp-empty { text-align: center; padding: 1.8rem 1rem; }
        .cmp-empty-ring { display: inline-flex; align-items: center; justify-content: center; width: 3.6rem; height: 3.6rem; border-radius: 999px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); margin-bottom: .8rem; }
        .dark .cmp-empty-ring { background: linear-gradient(135deg, rgb(255 255 255 / .06), rgb(45 25 236 / .25)); }
        .cmp-empty-ring svg { width: 1.7rem; height: 1.7rem; color: #2d19ec; }
        .dark .cmp-empty-ring svg { color: #a5b4fc; }
        .cmp-empty-title { font-weight: 600; margin-bottom: .3rem; }
        .cmp-empty-body { font-size: .85rem; color: rgb(107 114 128); max-width: 26rem; margin: 0 auto .9rem; }
        .dark .cmp-empty-body { color: #a1a1aa; }
        @media (max-width: 700px) { .cmp-spark { display: none; } .cmp-new { display: none; } }
    </style>

    <div class="wi-load-wrap">
        <div wire:loading.delay class="wi-load-overlay"><div class="wi-load-spinner"></div></div>

    <x-filament::section>
        <x-slot name="heading">{{ __('widgets.competitors_title') }}</x-slot>

        @if ($rows === [])
            {{-- Nothing tracked yet: invite instead of an empty box. --}}
            <div class="cmp-empty">
                <span class="cmp-empty-ring">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/></svg>
                </span>
                <div class="cmp-empty-title">{{ __('widgets.competitors_empty_title') }}</div>
                <div class="cmp-empty-body">{{ __('widgets.competitors_empty_body') }}</div>
                <x-filament::button tag="a" :href="\App\Filament\App\Pages\Competitors::getUrl()" size="sm">
                    {{ __('widgets.competitors_empty_cta') }}
                </x-filament::button>
            </div>
        @else
            @foreach ($rows as $row)
                <div class="cmp-row">
                    <span class="cmp-name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>

                    <span class="cmp-ratings">
                        <span class="cmp-you">{{ $row['ownRating'] !== null ? number_format($row['ownRating'], 1).'★' : '—' }}</span>
                        <span class="cmp-vs">{{ __('widgets.competitors_vs') }}</span>
                        <span>{{ $row['theirRating'] !== null ? number_format($row['theirRating'], 1).'★' : '—' }}</span>
                    </span>

                    @if ($row['delta'] !== null)
                        @php $chip = $row['delta'] > 0 ? 'up' : ($row['delta'] < 0 ? 'down' : 'flat'); @endphp
                        <span class="cmp-chip cmp-chip-{{ $chip }}">
                            @if ($chip === 'up') {{ __('pages/competitors.vs_ahead', ['delta' => number_format($row['delta'], 1)]) }}
                            @elseif ($chip === 'down') {{ __('pages/competitors.vs_behind', ['delta' => number_format(abs($row['delta']), 1)]) }}
                            @else {{ __('pages/competitors.vs_tied') }}
                            @endif
                        </span>
                    @endif

                    <span class="cmp-new">
                        {{ __('widgets.competitors_new_reviews') }}:
                        <strong>{{ __('widgets.competitors_you') }} +{{ number_format($row['ownNew']) }}</strong>
                        · {{ __('widgets.competitors_them') }} {{ $row['theirNew'] !== null ? '+'.number_format($row['theirNew']) : '—' }}
                    </span>

                    <span class="cmp-spark">{{ $row['spark'] ?? '' }}</span>
                </div>
            @endforeach

            @if ($total > $maxRows)
                <div class="cmp-hint">{{ __('widgets.competitors_more', ['count' => $total - $maxRows]) }}</div>
            @endif
        @endif
    </x-filament::section>
    </div>
</x-filament-widgets::widget>
