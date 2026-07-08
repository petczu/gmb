<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/competitors.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/competitors.not_configured_body') }}</div>
        </div>
    @else
        <div class="hint-box" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <span>{{ __('pages/competitors.intro') }}</span>
            <span style="display:inline-flex; align-items:center; gap:.5rem; flex-shrink:0; flex-wrap:wrap;">
                <select wire:model.live="trendPeriod"
                        style="border:1px solid #e5e7eb; border-radius:.6rem; padding:.4rem .7rem; font-size:.85rem; background:#fff;">
                    @foreach (__('common.periods') as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if ($this->trendPeriod === 'custom')
                    <input type="date" wire:model.live="trendFrom" max="{{ now()->toDateString() }}"
                           style="border:1px solid #e5e7eb; border-radius:.6rem; padding:.35rem .6rem; font-size:.85rem; background:#fff;">
                    <span style="color:#9ca3af;">–</span>
                    <input type="date" wire:model.live="trendTo" max="{{ now()->toDateString() }}"
                           style="border:1px solid #e5e7eb; border-radius:.6rem; padding:.35rem .6rem; font-size:.85rem; background:#fff;">
                @endif
            </span>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
