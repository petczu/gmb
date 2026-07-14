<x-filament-panels::page>
    @if (! $this->isPro())
        <x-pro-gate
            icon="sparkles"
            :title="__('pages/mcp.pro_only_title')"
            :body="__('pages/mcp.pro_only_body')"
            :cta="__('pages/mcp.see_plans')"
        />
    @else
        <div class="hint-box" style="display:grid; gap:.6rem;">
            <div>
                <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/mcp.endpoint') }}</div>
                <code style="font-size:.9rem; word-break:break-all;">{{ $this->endpoint() }}</code>
            </div>
            <div style="font-size:.85rem; color:#6b7280;">
                {{ __('pages/mcp.connect_help') }}<br>
                <a href="{{ route('docs.show', 'mcp') }}" target="_blank" rel="noopener"
                   style="display:inline-block; margin-top:.45rem; color:#2d19ec; font-weight:600; text-decoration:none;">
                    {{ __('pages/mcp.docs_link') }} &rarr;
                </a>
            </div>
        </div>

        {{ $this->form }}
    @endif
</x-filament-panels::page>
