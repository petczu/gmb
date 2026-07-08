<x-filament-panels::page>
    @if (! \App\Models\Location::query()->exists())
        {{-- No location yet: show the builder with a sample report preview and
             the same floating connect-first invite as the dashboard. --}}
        @include('filament.app.dashboard-demo-overlay')

        {{ $this->form }}

        <div style="margin-top:1rem; border:1px solid rgb(229 231 235); border-radius:0.75rem; overflow:hidden; background:#fff; color:#111827; padding:clamp(1.25rem,3vw,2.25rem);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; border-bottom:3px solid #2d19ec; padding-bottom:1rem;">
                <div>
                    <div style="font-size:1.35rem; font-weight:800;">{{ __('pages/reports.demo_business') }}</div>
                    <div style="color:#6b7280; font-size:.85rem;">{{ __('pages/reports.demo_period') }}</div>
                </div>
                <div style="color:#9ca3af; font-size:.8rem;">{{ __('pages/dashboard.demo_title') }}</div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(10rem, 1fr)); gap:.9rem; margin:1.25rem 0;">
                @foreach ([
                    ['label' => __('widgets.reviews_received'), 'value' => '38', 'delta' => '+9'],
                    ['label' => __('widgets.average_rating'), 'value' => '4.60★', 'delta' => '+0.2'],
                    ['label' => __('pages/reports.demo_five_star'), 'value' => '63%', 'delta' => '24 / 38'],
                    ['label' => __('widgets.response_rate'), 'value' => '92%', 'delta' => '+4 pp'],
                ] as $kpi)
                    <div style="border:1px solid #e5e7eb; border-radius:.7rem; padding:.85rem 1rem;">
                        <div style="font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af;">{{ $kpi['label'] }}</div>
                        <div style="font-size:1.45rem; font-weight:800; margin:.15rem 0;">{{ $kpi['value'] }}</div>
                        <div style="font-size:.75rem; color:#16a34a;">{{ $kpi['delta'] }}</div>
                    </div>
                @endforeach
            </div>

            <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; font-weight:700; margin-bottom:.4rem;">{{ __('pages/reports.demo_summary_label') }}</div>
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:.6rem; padding:.9rem 1.1rem; font-size:.88rem; line-height:1.6; color:#374151; margin-bottom:1.25rem;">
                {{ __('pages/reports.demo_summary') }}
            </div>

            <div style="display:grid; gap:.35rem; max-width:28rem;">
                @foreach ([5 => 24, 4 => 8, 3 => 3, 2 => 2, 1 => 1] as $star => $count)
                    <div style="display:flex; align-items:center; gap:.6rem; font-size:.8rem;">
                        <span style="width:1.6rem; color:#6b7280;">{{ $star }}★</span>
                        <span style="flex:1; height:.55rem; background:#f3f4f6; border-radius:999px; overflow:hidden;">
                            <span style="display:block; height:100%; width:{{ (int) round($count / 38 * 100) }}%; background:{{ ['#dc2626', '#ea580c', '#ca8a04', '#65a30d', '#16a34a'][$star - 1] }};"></span>
                        </span>
                        <span style="width:1.6rem; text-align:end; color:#6b7280;">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
    {{ $this->form }}

    {{-- Generate / Download sit AFTER the Report content section. Download stays
         disabled until a report has actually been generated for this selection. --}}
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-top:1rem;">
        @can('generate_reports')
        <x-filament::button
            wire:click="mountAction('generate')"
            wire:target="generate"
            wire:loading.attr="disabled"
            icon="heroicon-o-sparkles"
        >
            <span wire:loading.remove wire:target="generate">{{ __('pages/reports.generate_report') }}</span>
            <span wire:loading wire:target="generate">{{ __('pages/reports.generating') }}</span>
        </x-filament::button>
        @endcan

        {{-- Always active: downloading renders the same content as the preview
             (cached AI summary or the deterministic fallback) — no AI spend. --}}
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

        <x-filament::button
            wire:click="mountAction('schedule')"
            color="gray"
            outlined
            icon="heroicon-o-clock"
        >
            {{ __('pages/reports.schedule_report') }}
        </x-filament::button>

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
    @endif
</x-filament-panels::page>
