<x-filament-panels::page>
    {{-- Dashboard-wide filters: everything below (cards, chart, breakdowns,
         call log) reads through this selection. --}}
    <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:.9rem 1.25rem; background:#fff; display:grid; grid-template-columns:repeat(auto-fit, minmax(10rem, 1fr)); gap:.75rem; align-items:end;">
        <label style="display:block;">
            <span style="font-size:.72rem; font-weight:600; color:#6b7280;">Workspace</span>
            <select wire:model.live="fWorkspace" style="width:100%; margin-top:.25rem; border:1px solid #d1d5db; border-radius:.5rem; padding:.4rem .6rem; font-size:.85rem; background:#fff;">
                <option value="">All</option>
                @foreach ($filterOptions['workspaces'] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:block;">
            <span style="font-size:.72rem; font-weight:600; color:#6b7280;">Feature</span>
            <select wire:model.live="fReason" style="width:100%; margin-top:.25rem; border:1px solid #d1d5db; border-radius:.5rem; padding:.4rem .6rem; font-size:.85rem; background:#fff;">
                <option value="">All</option>
                @foreach ($filterOptions['reasons'] as $reason)
                    <option value="{{ $reason }}">{{ $reason }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:block;">
            <span style="font-size:.72rem; font-weight:600; color:#6b7280;">Model</span>
            <select wire:model.live="fModel" style="width:100%; margin-top:.25rem; border:1px solid #d1d5db; border-radius:.5rem; padding:.4rem .6rem; font-size:.85rem; background:#fff; font-family:monospace;">
                <option value="">All</option>
                @foreach ($filterOptions['models'] as $model)
                    <option value="{{ $model }}">{{ $model }}</option>
                @endforeach
            </select>
        </label>
        <label style="display:block;">
            <span style="font-size:.72rem; font-weight:600; color:#6b7280;">From</span>
            <input type="date" wire:model.live="fFrom" max="{{ now()->toDateString() }}" style="width:100%; margin-top:.25rem; border:1px solid #d1d5db; border-radius:.5rem; padding:.35rem .6rem; font-size:.85rem; background:#fff;">
        </label>
        <label style="display:block;">
            <span style="font-size:.72rem; font-weight:600; color:#6b7280;">Until</span>
            <input type="date" wire:model.live="fUntil" max="{{ now()->toDateString() }}" style="width:100%; margin-top:.25rem; border:1px solid #d1d5db; border-radius:.5rem; padding:.35rem .6rem; font-size:.85rem; background:#fff;">
        </label>
        <div>
            <button type="button" wire:click="$set('fWorkspace', null); $set('fReason', null); $set('fModel', null); $set('fFrom', null); $set('fUntil', null)"
                style="border:1px solid #d1d5db; background:#fff; border-radius:.5rem; padding:.4rem .8rem; font-size:.82rem; font-weight:600; cursor:pointer;">Reset</button>
        </div>
    </div>

    {{-- Headline cards --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr)); gap:1rem;">
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600;">Spend {{ $hasPeriod ? '(selected period)' : 'this month' }}</div>
            <div style="font-size:1.5rem; font-weight:800; color:#111827;">${{ number_format($stats['this_month'], 2) }}</div>
            <div style="font-size:.78rem; color:#9ca3af;">last month ${{ number_format($stats['last_month'], 2) }}</div>
        </div>
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600;">AI calls {{ $hasPeriod ? '(selected period)' : 'this month' }}</div>
            <div style="font-size:1.5rem; font-weight:800; color:#111827;">{{ number_format($stats['calls']) }}</div>
            <div style="font-size:.78rem; color:#9ca3af;">all time ${{ number_format($stats['total'], 2) }}</div>
        </div>
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600;">Tokens {{ $hasPeriod ? '(selected period)' : 'this month' }}</div>
            <div style="font-size:1.5rem; font-weight:800; color:#111827;">{{ number_format($stats['input_tokens'] + $stats['output_tokens']) }}</div>
            <div style="font-size:.78rem; color:#9ca3af;">{{ number_format($stats['input_tokens']) }} in · {{ number_format($stats['output_tokens']) }} out</div>
        </div>
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600;">Monthly budget</div>
            @if ($budget !== null)
                <div style="font-size:1.5rem; font-weight:800; color:{{ $budgetPercent >= 100 ? '#b91c1c' : ($budgetPercent >= 80 ? '#b45309' : '#111827') }};">
                    {{ $budgetPercent }}%
                </div>
                <div style="height:.45rem; background:#f3f4f6; border-radius:999px; overflow:hidden; margin:.35rem 0;">
                    <div style="height:100%; width:{{ $budgetPercent }}%; border-radius:999px; background:{{ $budgetPercent >= 100 ? '#dc2626' : ($budgetPercent >= 80 ? '#f59e0b' : '#16a34a') }};"></div>
                </div>
                <div style="font-size:.78rem; color:#9ca3af;">${{ number_format($stats['this_month'], 2) }} of ${{ number_format($budget, 2) }}</div>
            @else
                <div style="font-size:.95rem; font-weight:600; color:#9ca3af; margin-top:.3rem;">Not set</div>
                <div style="font-size:.75rem; color:#9ca3af;">Set AI_MONTHLY_BUDGET_USD to get 80%/100% alerts.</div>
            @endif
        </div>
    </div>

    {{-- Daily spend chart (last 30 days) --}}
    <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
        <div style="font-size:.78rem; color:#6b7280; font-weight:600; margin-bottom:.75rem;">Daily spend {{ $hasPeriod ? '(selected period)' : '(last 30 days)' }}</div>
        <div style="display:flex; align-items:flex-end; gap:2px; height:90px;">
            @foreach ($byDay as $day)
                <div title="{{ $day['day'] }} — ${{ number_format($day['cost'], 4) }}"
                     style="flex:1; min-width:3px; border-radius:2px 2px 0 0; background:#2d19ec; opacity:.85; height:{{ $day['cost'] > 0 ? max(4, (int) round($day['cost'] / $maxDay * 90)) : 2 }}px; {{ $day['cost'] <= 0 ? 'background:#e5e7eb;' : '' }}"></div>
            @endforeach
        </div>
        <div style="display:flex; justify-content:space-between; font-size:.7rem; color:#9ca3af; margin-top:.35rem;">
            <span>{{ $byDay[0]['day'] ?? '' }}</span>
            <span>{{ $byDay[count($byDay) - 1]['day'] ?? '' }}</span>
        </div>
    </div>

    {{-- Breakdowns --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(16rem, 1fr)); gap:1rem;">
        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600; margin-bottom:.6rem;">Top workspaces</div>
            @forelse ($byWorkspace as $row)
                <div style="display:flex; justify-content:space-between; font-size:.85rem; padding:.3rem 0; border-bottom:1px solid #f3f4f6;">
                    <span style="color:#111827; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:60%;">{{ $row['name'] }}</span>
                    <span style="color:#6b7280;">${{ number_format($row['cost'], 3) }} · {{ $row['calls'] }}</span>
                </div>
            @empty
                <div style="font-size:.85rem; color:#9ca3af;">No usage this month.</div>
            @endforelse
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600; margin-bottom:.6rem;">By feature</div>
            @forelse ($byReason as $row)
                <div style="display:flex; justify-content:space-between; font-size:.85rem; padding:.3rem 0; border-bottom:1px solid #f3f4f6;">
                    <span style="color:#111827;">{{ $row->reason }}</span>
                    <span style="color:#6b7280;">${{ number_format((float) $row->cost, 3) }} · {{ $row->calls }}</span>
                </div>
            @empty
                <div style="font-size:.85rem; color:#9ca3af;">No usage this month.</div>
            @endforelse
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:.9rem; padding:1rem 1.25rem; background:#fff;">
            <div style="font-size:.78rem; color:#6b7280; font-weight:600; margin-bottom:.6rem;">By model</div>
            @forelse ($byModel as $row)
                <div style="display:flex; justify-content:space-between; font-size:.85rem; padding:.3rem 0; border-bottom:1px solid #f3f4f6;">
                    <span style="color:#111827; font-family:monospace; font-size:.8rem;">{{ $row->model }}</span>
                    <span style="color:#6b7280;">${{ number_format((float) $row->cost, 3) }} · {{ number_format((int) $row->input + (int) $row->output) }} tok</span>
                </div>
            @empty
                <div style="font-size:.85rem; color:#9ca3af;">No usage this month.</div>
            @endforelse
        </div>
    </div>

    {{-- Raw call log --}}
    {{ $this->table }}
</x-filament-panels::page>
