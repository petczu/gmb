<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/competitors.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/competitors.not_configured_body') }}</div>
        </div>
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
