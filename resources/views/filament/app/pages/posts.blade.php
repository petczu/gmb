<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div style="border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:.9rem; padding:1.25rem 1.5rem;">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/posts.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/posts.not_configured_body') }}</div>
        </div>
    @else
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:1.25rem; font-size:.85rem; color:#6b7280;">
            {{ __('pages/posts.intro') }}
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
