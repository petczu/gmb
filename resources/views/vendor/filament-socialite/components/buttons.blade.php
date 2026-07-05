{{-- Published override: labels are run through __() so they localize per-request.
     The provider label is set to a translation KEY (see AppPanelProvider). --}}
<div
    x-data="{}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('filament-socialite-styles', package: 'filament-socialite'))]"
>
    <div class="flex flex-col gap-y-6">
        @if ($messageBag->isNotEmpty())
            @foreach($messageBag->all() as $value)
                <p class="fi-fo-field-wrp-error-message text-danger-600 dark:text-danger-400">{{ __($value) }}</p>
            @endforeach
        @endif

        @if (count($visibleProviders))
            @if($showDivider)
                {{-- Plain "or" divider: no pill background, just the line broken
                     by small muted text (inline styles — the vendor's Tailwind
                     utility classes aren't part of the compiled panel CSS). --}}
                <div style="position:relative; display:flex; align-items:center; justify-content:center; text-align:center;">
                    <div style="position:absolute; inset-inline:0; top:50%; border-top:1px solid #e5e7eb; height:1px;"></div>
                    <p style="position:relative; margin:0; background:#fff; padding:0 .8rem; font-size:.8rem; color:#9ca3af;">
                        {{ __('filament-socialite::auth.login-via') }}
                    </p>
                </div>
            @endif

            <div class="grid @if(count($visibleProviders) > 1) grid-cols-2 @endif gap-4">
                @foreach($visibleProviders as $key => $provider)
                    <x-filament::button
                        :color="$provider->getColor()"
                        :outlined="$provider->getOutlined()"
                        :icon="$provider->getIcon()"
                        tag="a"
                        :href="route($socialiteRoute, $key)"
                        :spa-mode="false"
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
