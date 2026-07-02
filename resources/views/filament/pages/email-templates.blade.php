<x-filament-panels::page>
    <div style="display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1.5rem; align-items:start;">
        <div>
            {{ $this->form }}
        </div>

        @php($preview = $this->previewHtml())
        <div style="position:sticky; top:1rem;">
            <div style="font-weight:600; font-size:.9rem; margin-bottom:.5rem; color:rgb(107 114 128);">
                Live preview
            </div>
            {{-- Key on the content so Livewire recreates the iframe whenever the
                 preview changes (browsers don't always re-render a morphed srcdoc). --}}
            <iframe
                wire:key="email-preview-{{ md5($preview) }}"
                srcdoc="{{ $preview }}"
                style="width:100%; height:820px; border:1px solid #e5e7eb; border-radius:12px; background:#fff;"
            ></iframe>
        </div>
    </div>
</x-filament-panels::page>
