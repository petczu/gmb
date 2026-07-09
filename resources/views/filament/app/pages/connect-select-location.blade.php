<x-filament-panels::page>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

    {{-- Full-page overlay while a location is being connected --}}
    <div
        wire:loading.flex
        wire:target="select"
        class="load-overlay"
        style="position:fixed; inset:0; z-index:50; display:none; align-items:center; justify-content:center; backdrop-filter:blur(1px);"
    >
        <div class="load-card" style="display:flex; flex-direction:column; align-items:center; gap:0.75rem; padding:1.5rem 2rem; border-radius:1rem;">
            <svg style="width:2rem; height:2rem; color:#2d19ec; animation:spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.3" />
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
            <div style="font-weight:600;">{{ __('onboarding.connecting_location') }}</div>
        </div>
    </div>

    <p class="fi-text-sm" style="color: rgb(107 114 128); margin-bottom: 0.5rem;">
        {{ __('onboarding.choose_location') }}
    </p>

    @if ($error)
        <x-filament::section>
            <x-slot name="heading">{{ $pendingExpired ? __('onboarding.pending_expired_title') : __('onboarding.could_not_load') }}</x-slot>
            <p @class(['muted-text' => $pendingExpired]) @style(['color: rgb(220 38 38)' => ! $pendingExpired])>{{ $error }}</p>
            <x-slot name="footerActions">
                @if ($pendingExpired)
                    <x-filament::button tag="a" href="{{ route('zernio.google.connect') }}">{{ __('onboarding.reconnect_google') }}</x-filament::button>
                @endif
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
                <div class="panel-card" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:0.875rem 1rem; border:1px solid var(--card-border); border-radius:0.75rem;">
                    <div style="display:flex; align-items:center; gap:0.75rem; min-width:0;">
                        <x-filament::icon icon="heroicon-o-map-pin" class="muted-text" style="width:1.25rem; height:1.25rem; flex:none;" />
                        <div style="min-width:0;">
                            <div style="font-weight:600;">{{ $location['name'] }}</div>
                            @if ($location['address'])
                                <div class="fi-text-sm muted-text">{{ $location['address'] }}</div>
                            @endif
                        </div>
                    </div>

                    @if ($this->isConnected($location['id']))
                        <span class="ok-pill" style="flex:none; display:inline-flex; align-items:center; gap:0.35rem; padding:0.4rem 0.9rem; border-radius:0.5rem; font-size:0.875rem; font-weight:600;">
                            <x-filament::icon icon="heroicon-m-check-circle" style="width:1rem; height:1rem;" />
                            {{ __('onboarding.connected') }}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="select('{{ $location['id'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="select('{{ $location['id'] }}')"
                            style="flex:none; display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer; padding:0.4rem 0.9rem; border-radius:0.5rem; background:rgb(24 0 255); color:#fff; font-size:0.875rem; font-weight:600; border:0;"
                        >
                            <span wire:loading.remove wire:target="select('{{ $location['id'] }}')">{{ __('onboarding.connect') }}</span>
                            {{-- inline-flex keeps spinner + label on ONE line (the
                                 CSS reset makes svg display:block otherwise). --}}
                            <span wire:loading.inline-flex wire:target="select('{{ $location['id'] }}')" style="align-items:center; gap:0.4rem;">
                                <svg style="width:1rem; height:1rem; flex:none; animation:spin 0.8s linear infinite;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
