<x-filament-panels::page>
    @if (! $this->isPro())
        <x-pro-gate
            icon="bolt"
            :title="__('pages/webhooks.pro_only_title')"
            :body="__('pages/webhooks.pro_only_body')"
            :cta="__('pages/webhooks.see_plans')"
        />
    @else
        <div class="hint-box">
            {{ __('pages/webhooks.intro') }}<br>
            <a href="{{ route('docs.show', 'webhooks') }}" target="_blank" rel="noopener"
               style="display:inline-block; margin-top:.45rem; color:#2d19ec; font-weight:600; text-decoration:none;">
                {{ __('pages/webhooks.docs_link') }} &rarr;
            </a>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
