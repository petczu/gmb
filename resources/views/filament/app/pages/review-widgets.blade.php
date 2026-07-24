<x-filament-panels::page>
    {{-- Theme-aware styling (the app toggles `.dark` on <html>), mirroring the
         review-pages configurator so light and dark both read well. --}}
    <style>
        .rww-empty { text-align:center; padding:3.5rem 1.5rem; border:1px solid rgb(0 0 0 / .08); border-radius:1rem; background:#fff; position:relative; overflow:hidden; }
        .dark .rww-empty { background:#18181b; border-color: rgb(255 255 255 / .1); }
        .rww-empty::before { content:''; position:absolute; inset:-40% -20% auto; height:70%; background:radial-gradient(ellipse at top, rgb(45 25 236 / .07), transparent 65%); pointer-events:none; }
        .rww-empty .ring { display:inline-flex; align-items:center; justify-content:center; width:4.5rem; height:4.5rem; border-radius:999px; background:linear-gradient(135deg, #eef2ff, #e0e7ff); margin-bottom:1rem; }
        .dark .rww-empty .ring { background:linear-gradient(135deg, rgb(255 255 255 / .06), rgb(45 25 236 / .25)); }
        .rww-empty .ring svg { width:2.1rem; height:2.1rem; color:#2d19ec; }
        .dark .rww-empty .ring svg { color:#a5b4fc; }
        .rww-empty h2 { font-size:1.15rem; font-weight:700; margin-bottom:.45rem; color:#111827; }
        .dark .rww-empty h2 { color:#f4f4f5; }
        .rww-empty p { max-width:34rem; margin:0 auto 1.4rem; font-size:.92rem; line-height:1.6; color:#6b7280; }
        .dark .rww-empty p { color:#a1a1aa; }

        .rww-row { display:flex; align-items:center; gap:1rem; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff; flex-wrap:wrap; }
        .dark .rww-row { background:#18181b; border-color: rgb(255 255 255 / .1); }
        .rww-name { font-weight:700; color:#111827; }
        .dark .rww-name { color:#f4f4f5; }
        .rww-meta { font-size:.8rem; color:#9ca3af; margin-top:.2rem; }
        .rww-btn { border:1px solid #e5e7eb; background:#fff; border-radius:.6rem; padding:.45rem .9rem; font-size:.85rem; font-weight:600; color:#111827; cursor:pointer; }
        .dark .rww-btn { background:transparent; border-color: rgb(255 255 255 / .14); color:#e4e4e7; }
        .rww-btn-danger { border-color:#fecaca; color:#b91c1c; }
        .dark .rww-btn-danger { border-color: rgb(220 38 38 / .4); color:#fca5a5; }
        .rww-badge-on { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600; }
        .dark .rww-badge-on { background: rgb(21 128 61 / .18); color:#86efac; border-color: rgb(134 239 172 / .25); }
        .rww-badge-off { background:#f3f4f6; color:#6b7280; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600; }
        .dark .rww-badge-off { background: rgb(255 255 255 / .08); color:#a1a1aa; }

        .rww-preview-panel { border:1px solid #e5e7eb; border-radius:1rem; background:#fafafa; padding:1.25rem; min-height:20rem; }
        .dark .rww-preview-panel { background:#111113; border-color: rgb(255 255 255 / .1); }
        .rww-preview-label { font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; font-weight:700; margin-bottom:.9rem; }
        .rww-note { font-size:.8rem; color:#9ca3af; margin-top:.6rem; }
    </style>

    @if (! $this->editing)
        @php $list = $this->widgetsList(); @endphp

        @if ($list === [])
            <div class="rww-empty">
                <span class="ring">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"/></svg>
                </span>
                <h2>{{ __('pages/review_widgets.empty_title') }}</h2>
                <p>{{ __('pages/review_widgets.empty_desc') }}</p>
                <x-filament::button wire:click="newWidget" size="lg" icon="heroicon-o-plus">
                    {{ __('pages/review_widgets.new_widget') }}
                </x-filament::button>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:.75rem;">
                @foreach ($list as $row)
                    <div class="rww-row">
                        <div style="flex:1; min-width:14rem;">
                            <div style="display:flex; align-items:center; gap:.5rem;">
                                <span class="rww-name">{{ $row['name'] }}</span>
                                @if ($row['active'])
                                    <span class="rww-badge-on">{{ __('pages/review_widgets.status_active') }}</span>
                                @else
                                    <span class="rww-badge-off">{{ __('pages/review_widgets.status_inactive') }}</span>
                                @endif
                            </div>
                            <div class="rww-meta">
                                {{ __('pages/review_widgets.layout_'.$row['layout']) }} · {{ trans_choice('pages/review_widgets.review_count', $row['count'], ['count' => $row['count']]) }}
                            </div>
                        </div>
                        <div style="display:flex; gap:.5rem;">
                            <button type="button" wire:click="edit({{ $row['id'] }})" class="rww-btn">
                                {{ __('pages/review_widgets.list_edit') }}
                            </button>
                            <button type="button" wire:click="deleteFromList({{ $row['id'] }})"
                                    wire:confirm="{{ __('pages/review_widgets.delete_confirm') }}"
                                    class="rww-btn rww-btn-danger">
                                {{ __('pages/review_widgets.delete') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- ============ EDITOR: form left, live preview right ============ --}}
        <div style="display:grid; grid-template-columns: minmax(0, 420px) minmax(0, 1fr); gap:1.5rem; align-items:start;">
            <div>
                {{ $this->form }}
            </div>

            <div style="position:sticky; top:1rem;">
                <div class="rww-preview-panel">
                    <div class="rww-preview-label">{{ __('pages/review_widgets.preview') }}</div>
                    {!! $this->previewMarkup() !!}
                </div>
                @if ($this->widgetId === null)
                    <p class="rww-note">{{ __('pages/review_widgets.preview_demo_note') }}</p>
                @else
                    <p class="rww-note">{{ __('pages/review_widgets.preview_saved_note') }}</p>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
