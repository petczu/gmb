<x-filament-panels::page>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

    {{-- Full-page overlay while a location is being connected --}}
    <div
        wire:loading.flex
        wire:target="select"
        style="position:fixed; inset:0; z-index:50; display:none; align-items:center; justify-content:center; background:rgba(255,255,255,0.55); backdrop-filter:blur(1px);"
    >
        <div style="display:flex; flex-direction:column; align-items:center; gap:0.75rem; padding:1.5rem 2rem; border-radius:1rem; background:#fff; box-shadow:0 10px 40px rgba(0,0,0,0.12);">
            <svg style="width:2rem; height:2rem; color:rgb(245 158 11); animation:spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.3" />
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            <div style="font-weight:600; color:rgb(55 65 81);">{{ __('onboarding.connecting_location') }}</div>
        </div>
    </div>

    <p class="fi-text-sm" style="color: rgb(107 114 128); margin-bottom: 0.5rem;">
        {{ __('onboarding.choose_location') }}
    </p>

    @if ($error)
        <x-filament::section>
            <x-slot name="heading">{{ __('onboarding.could_not_load') }}</x-slot>
            <p style="color: rgb(220 38 38);">{{ $error }}</p>
            <x-slot name="footerActions">
                <x-filament::button tag="a" href="/locations" color="gray">{{ __('onboarding.back') }}</x-filament::button>
            </x-slot>
        </x-filament::section>
    @elseif (count($locations) === 0)
        <x-filament::section>
            <x-slot name="heading">{{ __('onboarding.no_locations_available') }}</x-slot>
            <p style="color: rgb(107 114 128);">{{ __('onboarding.no_locations_body') }}</p>
        </x-filament::section>
    @else
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem;">
            <p class="fi-text-sm" style="color: rgb(107 114 128);">{{ __('onboarding.connect_then_done') }}</p>
            <x-filament::button wire:click="finish" color="gray" size="sm">{{ __('onboarding.done') }}</x-filament::button>
        </div>
        <div style="display:flex; flex-direction:column; gap:0.5rem;">
            @foreach ($locations as $location)
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:0.875rem 1rem; border:1px solid rgb(229 231 235); border-radius:0.75rem; background:#fff;">
                    <div style="display:flex; align-items:center; gap:0.75rem; min-width:0;">
                        <x-filament::icon icon="heroicon-o-map-pin" style="width:1.25rem; height:1.25rem; color: rgb(156 163 175); flex:none;" />
                        <div style="min-width:0;">
                            <div style="font-weight:600;">{{ $location['name'] }}</div>
                            @if ($location['address'])
                                <div class="fi-text-sm" style="color: rgb(107 114 128);">{{ $location['address'] }}</div>
                            @endif
                        </div>
                    </div>

                    @if ($this->isConnected($location['id']))
                        <span style="flex:none; display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:0.5rem; background:rgb(220 252 231); color:rgb(22 101 52); font-size:0.875rem; font-weight:600;">
                            <x-filament::icon icon="heroicon-m-check-circle" style="width:1rem; height:1rem;" />
                            {{ __('onboarding.connected') }}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="select('{{ $location['id'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="select('{{ $location['id'] }}')"
                            style="flex:none; display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer; padding:0.4rem 0.9rem; border-radius:0.5rem; background:rgb(245 158 11); color:#fff; font-size:0.875rem; font-weight:600; border:0;"
                        >
                            <span wire:loading.remove wire:target="select('{{ $location['id'] }}')">{{ __('onboarding.connect') }}</span>
                            <span wire:loading wire:target="select('{{ $location['id'] }}')">
                                <svg style="width:1rem; height:1rem; vertical-align:-2px; margin-right:0.3rem; animation:spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.3" />
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                </svg>{{ __('onboarding.connecting') }}</span>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
