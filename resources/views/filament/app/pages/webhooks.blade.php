<x-filament-panels::page>
    @if (! $this->isPro())
        <div style="border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:.9rem; padding:1.25rem 1.5rem;">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/webhooks.pro_only_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/webhooks.pro_only_body') }}</div>
            <a href="{{ \App\Filament\App\Pages\Billing::getUrl() }}"
               style="display:inline-block; margin-top:.9rem; background:#1800ff; color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:.6rem; text-decoration:none;">
                {{ __('pages/webhooks.see_plans') }}
            </a>
        </div>
    @else
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:1.25rem; font-size:.85rem; color:#6b7280;">
            {{ __('pages/webhooks.intro') }}<br>
            <a href="{{ route('docs.show', 'webhooks') }}" target="_blank" rel="noopener"
               style="display:inline-block; margin-top:.45rem; color:#2d19ec; font-weight:600; text-decoration:none;">
                {{ __('pages/webhooks.docs_link') }} &rarr;
            </a>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
