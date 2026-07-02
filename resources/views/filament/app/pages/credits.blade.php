<x-filament-panels::page>
    <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1.1rem 1.4rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div style="display:flex; gap:2.5rem; flex-wrap:wrap;">
            <div>
                <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/credits.balance') }}</div>
                <div style="font-size:1.45rem; font-weight:700;">{{ number_format($balance) }}</div>
            </div>
            <div>
                <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/credits.spent_this_month') }}</div>
                <div style="font-size:1.45rem; font-weight:700;">{{ number_format($spentThisMonth) }}</div>
            </div>
            <div>
                <div style="font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af;">{{ __('pages/credits.total_used') }}</div>
                <div style="font-size:1.45rem; font-weight:700;">{{ number_format($totalUsed) }}</div>
            </div>
        </div>

        <a href="{{ \App\Filament\App\Pages\Billing::getUrl() }}"
           style="background:#1800ff; color:#fff; font-weight:600; padding:.55rem 1.1rem; border-radius:.6rem; text-decoration:none; white-space:nowrap;">
            {{ __('pages/credits.get_more') }}
        </a>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
