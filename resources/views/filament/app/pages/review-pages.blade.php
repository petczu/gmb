<x-filament-panels::page>
    @if (! $this->editing)
        {{-- ============ LIST: all review pages of the workspace ============ --}}
        @php $list = $this->pagesList(); @endphp

        @if ($list === [])
            {{-- Friendly first-run invite instead of a bare dashed box. --}}
            <style>
                .rp-empty { text-align:center; padding:3.5rem 1.5rem; border:1px solid rgb(0 0 0 / .08); border-radius:1rem; background:#fff; position:relative; overflow:hidden; }
                .dark .rp-empty { background:#18181b; border-color: rgb(255 255 255 / .1); }
                .rp-empty::before { content:''; position:absolute; inset:-40% -20% auto; height:70%; background:radial-gradient(ellipse at top, rgb(45 25 236 / .07), transparent 65%); pointer-events:none; }
                .rp-empty .ring { display:inline-flex; align-items:center; justify-content:center; width:4.5rem; height:4.5rem; border-radius:999px; background:linear-gradient(135deg, #eef2ff, #e0e7ff); margin-bottom:1rem; }
                .dark .rp-empty .ring { background:linear-gradient(135deg, rgb(255 255 255 / .06), rgb(45 25 236 / .25)); }
                .rp-empty .ring svg { width:2.1rem; height:2.1rem; color:#2d19ec; }
                .dark .rp-empty .ring svg { color:#a5b4fc; }
                .rp-empty h2 { font-size:1.15rem; font-weight:700; margin-bottom:.45rem; }
                .rp-empty p { max-width:34rem; margin:0 auto 1.4rem; font-size:.92rem; line-height:1.6; color:#6b7280; }
                .dark .rp-empty p { color:#a1a1aa; }
            </style>
            <div class="rp-empty">
                <span class="ring">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/></svg>
                </span>
                <h2>{{ __('pages/review_pages.empty_title') }}</h2>
                <p>{{ __('pages/review_pages.empty_list') }}</p>
                <x-filament::button wire:click="newPage" size="lg" icon="heroicon-o-plus">
                    {{ __('pages/review_pages.new_page') }}
                </x-filament::button>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:.75rem;">
                @foreach ($list as $row)
                    <div style="display:flex; align-items:center; gap:1rem; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff; flex-wrap:wrap;">
                        <div style="flex:1; min-width:14rem;">
                            <div style="display:flex; align-items:center; gap:.5rem;">
                                <span style="font-weight:700; color:#111827;">/r/{{ $row['slug'] }}</span>
                                @if ($row['active'])
                                    <span style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600;">{{ __('pages/review_pages.status_active') }}</span>
                                @else
                                    <span style="background:#f3f4f6; color:#6b7280; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600;">{{ __('pages/review_pages.status_inactive') }}</span>
                                @endif
                            </div>
                            <div style="font-size:.8rem; color:#9ca3af; margin-top:.2rem; word-break:break-all;">
                                {{ $row['url'] }}
                            </div>
                        </div>

                        <div style="display:flex; gap:1.5rem; font-size:.85rem; color:#6b7280;">
                            <span><strong style="color:#111827;">{{ number_format($row['views']) }}</strong> {{ __('pages/review_pages.stat_views_short') }}</span>
                            <span><strong style="color:#111827;">{{ number_format($row['clicks']) }}</strong> {{ __('pages/review_pages.stat_clicks_short') }}</span>
                        </div>

                        <div style="display:flex; gap:.5rem;">
                            <button type="button" wire:click="edit({{ $row['id'] }})"
                                    style="border:1px solid #e5e7eb; background:#fff; border-radius:.6rem; padding:.45rem .9rem; font-size:.85rem; font-weight:600; color:#111827; cursor:pointer;">
                                {{ __('pages/review_pages.list_edit') }}
                            </button>
                            <a href="{{ $row['url'] }}" target="_blank" rel="noopener"
                               style="border:1px solid #e5e7eb; background:#fff; border-radius:.6rem; padding:.45rem .9rem; font-size:.85rem; font-weight:600; color:#111827; text-decoration:none;">
                                {{ __('pages/review_pages.open_page') }}
                            </a>
                            <button type="button"
                                    wire:click="deleteFromList({{ $row['id'] }})"
                                    wire:confirm="{{ __('pages/review_pages.delete_page_desc') }}"
                                    style="border:1px solid #fecaca; background:#fff; border-radius:.6rem; padding:.45rem .7rem; font-size:.85rem; color:#dc2626; cursor:pointer;">
                                {{ __('pages/review_pages.delete_page') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- ============ EDITOR: one page + live preview ============ --}}
        @php
            $stats = $this->analytics();
            $labels = $this->targetLabels();
        @endphp

        @if ($this->pageId !== null)
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(10rem, 1fr)); gap:.75rem;">
                <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:.9rem 1.1rem;">
                    <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/review_pages.stat_views') }}</div>
                    <div style="font-size:1.4rem; font-weight:700;">{{ number_format($stats['views']) }}</div>
                </div>
                <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:.9rem 1.1rem;">
                    <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/review_pages.stat_clicks') }}</div>
                    <div style="font-size:1.4rem; font-weight:700;">{{ number_format($stats['clicks']) }}</div>
                </div>
                <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:.9rem 1.1rem;">
                    <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/review_pages.stat_ctr') }}</div>
                    <div style="font-size:1.4rem; font-weight:700;">{{ $stats['ctr'] }}%</div>
                </div>
            </div>

            {{-- Clicks per button — one row per configured button (zeros included). --}}
            @if ($labels !== [])
                <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:.9rem 1.1rem;">
                    <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; margin-bottom:.6rem;">
                        {{ __('pages/review_pages.clicks_by_button') }}
                    </div>
                    <div style="display:flex; flex-direction:column; gap:.45rem;">
                        @foreach ($labels as $key => $label)
                            @php
                                $count = $stats['perTarget'][$key] ?? 0;
                                $share = $stats['clicks'] > 0 ? (int) round($count / $stats['clicks'] * 100) : 0;
                            @endphp
                            <div style="display:flex; align-items:center; gap:.9rem;">
                                <span style="width:9rem; flex-shrink:0; font-size:.85rem; color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $label }}</span>
                                <span style="flex:1; height:.5rem; background:#f3f4f6; border-radius:999px; overflow:hidden;">
                                    <span style="display:block; height:100%; width:{{ $share }}%; background:#2d19ec; border-radius:999px;"></span>
                                </span>
                                <span style="width:7rem; flex-shrink:0; text-align:right; font-size:.85rem; color:#6b7280;">
                                    <strong style="color:#111827;">{{ number_format($count) }}</strong> · {{ $share }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <style>
            .rp-editor-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:1.25rem; align-items:start; }
            @media (min-width: 1024px) {
                .rp-editor-grid { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
                .rp-preview { position:sticky; top:5rem; }
            }
        </style>
        <div class="rp-editor-grid">
            <div>
                {{ $this->form }}
            </div>

            <div class="rp-preview"
                 x-data
                 x-init="window.addEventListener('message', (e) => { if (e.data && e.data.previewLang) { $wire.set('previewLang', e.data.previewLang) } })">
                {{-- Language is switched via the EN|DE links INSIDE the preview
                     (they postMessage previewLang up to this component). --}}
                <div style="font-size:.85rem; font-weight:600; color:#6b7280; margin-bottom:.5rem;">{{ __('pages/review_pages.live_preview') }}</div>

                @php $preview = $this->previewHtml(); @endphp
                <iframe
                    wire:key="review-page-preview-{{ md5($preview) }}"
                    srcdoc="{{ $preview }}"
                    style="width:100%; height:34rem; border:1px solid #e5e7eb; border-radius:.9rem; background:#fff;"
                    title="Preview"
                ></iframe>
            </div>
        </div>
    @endif
    @script
    <script>
        // Copy with a clipboard-API + execCommand fallback (http dev hosts
        // have no navigator.clipboard). Feedback: swap the icon to a check.
        window.copyTextToClipboard = function (text, btn) {
            var done = function () {
                if (btn.dataset.busy) return;
                btn.dataset.busy = '1';
                var old = btn.innerHTML;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="#16a34a" style="width:1rem; height:1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
                setTimeout(function () { btn.innerHTML = old; delete btn.dataset.busy; }, 1400);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var t = document.createElement('textarea');
                t.value = text;
                t.style.position = 'fixed';
                t.style.opacity = '0';
                document.body.appendChild(t);
                t.select();
                try { document.execCommand('copy'); } finally { t.remove(); }
                done();
            }
        };
    </script>
    @endscript
</x-filament-panels::page>
