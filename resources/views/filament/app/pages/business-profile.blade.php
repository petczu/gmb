<x-filament-panels::page>
    @if (! $this->isConfigured())
        <div class="warn-box">
            <div style="font-weight:700; margin-bottom:.25rem;">{{ __('pages/business_profile.not_configured_title') }}</div>
            <div style="font-size:.92rem;">{{ __('pages/business_profile.not_configured_body') }}</div>
        </div>
    @else
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:1rem; border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; margin-bottom:.25rem;">
            {{-- The page is opened from Locations → "Edit info" for ONE location,
                 so it shows the name instead of a picker. --}}
            <div style="min-width:16rem;">
                <div class="muted-text" style="font-size:.78rem; font-weight:600; margin-bottom:.3rem;">
                    {{ __('common.location') }}
                </div>
                <div style="font-weight:700; font-size:1.05rem;">
                    {{ \App\Models\Location::query()->find($this->locationId)?->name ?? '—' }}
                </div>
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
