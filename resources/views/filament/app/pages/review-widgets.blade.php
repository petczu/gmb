<x-filament-panels::page>
    @if (! $this->editing)
        @php $list = $this->widgetsList(); @endphp

        @if ($list === [])
            <div style="text-align:center; padding:3.5rem 1.5rem; border:1px solid rgb(0 0 0 / .08); border-radius:1rem; background:#fff;">
                <h2 style="font-size:1.15rem; font-weight:700; margin-bottom:.45rem;">{{ __('pages/review_widgets.empty_title') }}</h2>
                <p style="max-width:34rem; margin:0 auto 1.4rem; font-size:.92rem; line-height:1.6; color:#6b7280;">{{ __('pages/review_widgets.empty_desc') }}</p>
                <x-filament::button wire:click="newWidget" size="lg" icon="heroicon-o-plus">
                    {{ __('pages/review_widgets.new_widget') }}
                </x-filament::button>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:.75rem;">
                @foreach ($list as $row)
                    <div style="display:flex; align-items:center; gap:1rem; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff; flex-wrap:wrap;">
                        <div style="flex:1; min-width:14rem;">
                            <div style="display:flex; align-items:center; gap:.5rem;">
                                <span style="font-weight:700; color:#111827;">{{ $row['name'] }}</span>
                                @if ($row['active'])
                                    <span style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600;">{{ __('pages/review_widgets.status_active') }}</span>
                                @else
                                    <span style="background:#f3f4f6; color:#6b7280; border-radius:999px; padding:.1rem .55rem; font-size:.72rem; font-weight:600;">{{ __('pages/review_widgets.status_inactive') }}</span>
                                @endif
                            </div>
                            <div style="font-size:.8rem; color:#9ca3af; margin-top:.2rem;">
                                {{ __('pages/review_widgets.layout_'.$row['layout']) }} · {{ trans_choice('pages/review_widgets.review_count', $row['count'], ['count' => $row['count']]) }}
                            </div>
                        </div>
                        <div style="display:flex; gap:.5rem;">
                            <button type="button" wire:click="edit({{ $row['id'] }})"
                                    style="border:1px solid #e5e7eb; background:#fff; border-radius:.6rem; padding:.45rem .9rem; font-size:.85rem; font-weight:600; color:#111827; cursor:pointer;">
                                {{ __('pages/review_widgets.list_edit') }}
                            </button>
                            <button type="button" wire:click="deleteFromList({{ $row['id'] }})"
                                    wire:confirm="{{ __('pages/review_widgets.delete_confirm') }}"
                                    style="border:1px solid #fecaca; background:#fff; border-radius:.6rem; padding:.45rem .9rem; font-size:.85rem; font-weight:600; color:#b91c1c; cursor:pointer;">
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
                <div style="border:1px solid #e5e7eb; border-radius:1rem; background:#fafafa; padding:1.25rem; min-height:20rem;">
                    <div style="font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; font-weight:700; margin-bottom:.9rem;">
                        {{ __('pages/review_widgets.preview') }}
                    </div>
                    {!! $this->previewMarkup() !!}
                </div>
                @if ($this->widgetId === null)
                    <p style="font-size:.8rem; color:#9ca3af; margin-top:.6rem;">{{ __('pages/review_widgets.preview_demo_note') }}</p>
                @else
                    <p style="font-size:.8rem; color:#9ca3af; margin-top:.6rem;">{{ __('pages/review_widgets.preview_saved_note') }}</p>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
