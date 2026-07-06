<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div style="border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:.9rem; padding:1.25rem 1.5rem;">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/business_profile.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/business_profile.not_configured_body') }}</div>
        </div>
    @else
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:1rem; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:.25rem;">
            <div style="min-width:16rem;">
                <label style="display:block; font-size:.78rem; font-weight:600; color:#6b7280; margin-bottom:.3rem;">
                    {{ __('pages/business_profile.pick_location') }}
                </label>
                <select wire:model.live="locationId"
                        style="width:100%; border:1px solid #e5e7eb; border-radius:.6rem; padding:.5rem .75rem; font-size:.9rem; background:#fff;">
                    @foreach (\App\Models\Location::query()->orderBy('name')->get() as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($this->listingStatus !== null)
                <div style="display:flex; gap:.5rem; flex-wrap:wrap; font-size:.8rem;">
                    @if ($this->listingStatus['verified'])
                        <span style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:999px; padding:.25rem .7rem; font-weight:600;">
                            {{ __('pages/business_profile.status_live') }}
                        </span>
                    @else
                        <span style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; border-radius:999px; padding:.25rem .7rem; font-weight:600;">
                            {{ __('pages/business_profile.status_unverified') }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <form wire:submit="save">
            {{ $this->form }}
        </form>
    @endif
</x-filament-panels::page>
