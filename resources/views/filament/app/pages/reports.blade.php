<x-filament-panels::page>
    {{ $this->form }}

    {{-- Generate / Download sit AFTER the Report content section. Download stays
         disabled until a report has actually been generated for this selection. --}}
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-top:1rem;">
        <x-filament::button
            wire:click="mountAction('generate')"
            wire:target="generate"
            wire:loading.attr="disabled"
            icon="heroicon-o-sparkles"
        >
            <span wire:loading.remove wire:target="generate">{{ __('pages/reports.generate_report') }}</span>
            <span wire:loading wire:target="generate">{{ __('pages/reports.generating') }}</span>
        </x-filament::button>

        @if ($this->reportReady())
            <x-filament::button
                tag="a"
                :href="$this->downloadUrl()"
                target="_blank"
                color="gray"
                outlined
                icon="heroicon-o-arrow-down-tray"
            >
                {{ __('pages/reports.download_pdf') }}
            </x-filament::button>
        @else
            <x-filament::button
                color="gray"
                outlined
                disabled
                icon="heroicon-o-arrow-down-tray"
                :title="__('pages/reports.download_first_tooltip')"
            >
                {{ __('pages/reports.download_pdf') }}
            </x-filament::button>
        @endif

        @if ($label = $this->reportsLeftLabel())
            <span style="font-size:0.78rem; color:rgb(107 114 128); margin-left:0.25rem;">{{ $label }}</span>
        @endif
    </div>

    <style>@keyframes rpspin { to { transform: rotate(360deg); } }</style>
    <div
        wire:key="report-preview-{{ md5($this->previewUrl()) }}"
        x-data="{ loading: true }"
        style="position:relative; margin-top:1rem; border:1px solid rgb(229 231 235); border-radius:0.75rem; overflow:hidden; background:#fff; min-height:300px;"
    >
        {{-- Loading overlay until the iframe finishes rendering --}}
        <div x-show="loading" x-transition.opacity
             style="position:absolute; inset:0; z-index:5; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.6rem; background:#fff; color:rgb(107 114 128);">
            <svg style="width:2rem; height:2rem; color:rgb(245 158 11); animation:rpspin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.3" />
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            <span style="font-size:0.85rem;">{{ __('pages/reports.building') }}</span>
        </div>

        <iframe
            src="{{ $this->previewUrl() }}"
            @load="loading = false"
            style="width:100%; height:1100px; border:0; display:block;"
            :title="__('pages/reports.preview_title')"
        ></iframe>
    </div>
</x-filament-panels::page>
