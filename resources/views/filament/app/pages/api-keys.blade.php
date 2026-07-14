<x-filament-panels::page>
    @if (! $this->isPro())
        <x-pro-gate
            icon="key"
            :title="__('pages/api_keys.pro_only_title')"
            :body="__('pages/api_keys.pro_only_body')"
            :cta="__('pages/api_keys.see_plans')"
        />
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
