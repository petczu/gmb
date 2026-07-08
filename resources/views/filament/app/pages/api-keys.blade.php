<x-filament-panels::page>
    @if (! $this->isPro())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/api_keys.pro_only_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/api_keys.pro_only_body') }}</div>
            <a href="{{ \App\Filament\App\Pages\Billing::getUrl() }}"
               style="display:inline-block; margin-top:.9rem; background:#1800ff; color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:.6rem; text-decoration:none;">
                {{ __('pages/api_keys.see_plans') }}
            </a>
        </div>
    @else
        @if ($this->plainKey)
            <div class="ok-box">
                <div class="ok-box-title">{{ __('pages/api_keys.once_title') }}</div>
                <div class="ok-box-body">{{ __('pages/api_keys.once_body') }}</div>
                <code class="code-box">{{ $this->plainKey }}</code>
            </div>
        @endif

        <div class="hint-box">
            {{ __('pages/api_keys.base_url') }} <code style="font-size:.85rem;">{{ rtrim(config('app.url'), '/') }}/api/v1</code><br>
            {{ __('pages/api_keys.auth_hint') }}<br>
            <a href="{{ route('docs.index') }}" target="_blank" rel="noopener"
               style="display:inline-block; margin-top:.45rem; color:#2d19ec; font-weight:600; text-decoration:none;">
                {{ __('pages/api_keys.docs_link') }} &rarr;
            </a>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
