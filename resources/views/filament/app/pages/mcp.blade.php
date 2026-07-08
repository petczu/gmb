<x-filament-panels::page>
    @if (! $this->isPro())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/mcp.pro_only_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/mcp.pro_only_body') }}</div>
            <a href="{{ \App\Filament\App\Pages\Billing::getUrl() }}"
               style="display:inline-block; margin-top:.9rem; background:#1800ff; color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:.6rem; text-decoration:none;">
                {{ __('pages/mcp.see_plans') }}
            </a>
        </div>
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
