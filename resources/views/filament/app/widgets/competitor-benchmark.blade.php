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
        .cmp-empty { text-align: center; padding: 1.4rem 1rem; }
        .cmp-empty-title { font-weight: 600; margin-bottom: .3rem; }
        .cmp-empty-body { font-size: .85rem; color: rgb(107 114 128); margin-bottom: .9rem; }
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
