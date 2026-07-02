<x-filament-panels::page>
    @if (! $this->isPro())
        <div style="border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:.9rem; padding:1.25rem 1.5rem;">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/api_keys.pro_only_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/api_keys.pro_only_body') }}</div>
            <a href="{{ \App\Filament\App\Pages\Billing::getUrl() }}"
               style="display:inline-block; margin-top:.9rem; background:#1800ff; color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:.6rem; text-decoration:none;">
                {{ __('pages/api_keys.see_plans') }}
            </a>
        </div>
    @else
        @if ($this->plainKey)
            <div style="border:1px solid #bbf7d0; background:#f0fdf4; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:1.25rem;">
                <div style="font-weight:700; color:#166534; margin-bottom:.35rem;">{{ __('pages/api_keys.once_title') }}</div>
                <div style="font-size:.85rem; color:#15803d; margin-bottom:.6rem;">{{ __('pages/api_keys.once_body') }}</div>
                <code style="display:block; word-break:break-all; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; padding:.6rem .75rem; font-size:.85rem;">{{ $this->plainKey }}</code>
            </div>
        @endif

        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:1.25rem; font-size:.85rem; color:#6b7280;">
            {{ __('pages/api_keys.base_url') }} <code style="font-size:.85rem;">{{ rtrim(config('app.url'), '/') }}/api/v1</code><br>
            {{ __('pages/api_keys.auth_hint') }}
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
