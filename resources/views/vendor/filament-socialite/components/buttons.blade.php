{{-- Published override: labels are run through __() so they localize per-request.
     The provider label is set to a translation KEY (see AppPanelProvider). --}}
<div
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-socialite-styles', package: 'filament-socialite'))]"
>
    {{-- Layout uses inline styles throughout: the vendor's Tailwind utility
         classes (flex/gap/grid/w-full) aren't part of the compiled panel CSS,
         so relying on them left the divider glued to a shrink-wrapped, left-
         aligned button. --}}
    <div style="display:flex; flex-direction:column; gap:1.5rem;">
        @if ($messageBag->isNotEmpty())
            @foreach($messageBag->all() as $value)
                <p class="fi-fo-field-wrp-error-message text-danger-600 dark:text-danger-400">{{ __($value) }}</p>
            @endforeach
        @endif

        @if (count($visibleProviders))
            @if($showDivider)
                {{-- Plain "or" divider: no pill background, just the line broken
                     by small muted text. --}}
                <div style="position:relative; display:flex; align-items:center; justify-content:center; text-align:center;">
                    <div style="position:absolute; inset-inline:0; top:50%; border-top:1px solid #e5e7eb; height:1px;"></div>
                    <p style="position:relative; margin:0; background:#fff; padding:0 .8rem; font-size:.8rem; color:#9ca3af;">
                        {{ __('filament-socialite::auth.login-via') }}
                    </p>
                </div>
            @endif

            <div style="display:grid; gap:1rem; @if(count($visibleProviders) > 1) grid-template-columns:repeat(2, minmax(0, 1fr)); @endif">
                @foreach($visibleProviders as $key => $provider)
                    <x-filament::button
                        :color="$provider->getColor()"
                        :outlined="$provider->getOutlined()"
                        :icon="$provider->getIcon()"
                        tag="a"
                        :href="route($socialiteRoute, $key)"
                        :spa-mode="false"
                        style="width:100%; justify-content:center;"
                    >
                        {{ __($provider->getLabel()) }}
                    </x-filament::button>
                @endforeach
            </div>
        @else
            <span></span>
        @endif
    </div>
</div>
