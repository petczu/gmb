<x-filament-panels::page>
    @if (! $this->editing)
        {{-- ============ LIST: all review pages of the workspace ============ --}}
        @php $list = $this->pagesList(); @endphp

        @if ($list === [])
            <div style="border:1px dashed #d1d5db; border-radius:1rem; padding:2rem; text-align:center; color:#6b7280;">
                {{ __('pages/review_pages.empty_list') }}
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
