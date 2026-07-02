@php
    $badge = [
        'success' => ['#166534', '#dcfce7'],
        'failed' => ['#991b1b', '#fee2e2'],
        'pending' => ['#92400e', '#fef3c7'],
    ];
@endphp

@if ($deliveries->isEmpty())
    <div style="color:#6b7280; font-size:.9rem;">{{ __('pages/webhooks.no_deliveries') }}</div>
@else
    <div style="display:flex; flex-direction:column; gap:.5rem;">
        @foreach ($deliveries as $delivery)
            @php [$fg, $bg] = $badge[$delivery->status] ?? ['#374151', '#f3f4f6']; @endphp
            <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; border:1px solid #e5e7eb; border-radius:.6rem; padding:.55rem .75rem;">
                <div style="min-width:0;">
                    <div style="font-family:monospace; font-size:.82rem; color:#111827;">{{ $delivery->event }}</div>
                    <div style="font-size:.72rem; color:#9ca3af;">
                        {{ $delivery->last_attempt_at?->diffForHumans() ?? $delivery->created_at->diffForHumans() }}
                        · {{ __('pages/webhooks.attempts') }}: {{ $delivery->attempts }}
                        @if ($delivery->response_status)· HTTP {{ $delivery->response_status }}@endif
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:.6rem; flex-shrink:0;">
                    <span style="font-size:.72rem; font-weight:600; color:{{ $fg }}; background:{{ $bg }}; padding:.15rem .5rem; border-radius:.4rem;">
                        {{ __('pages/webhooks.status_'.$delivery->status) }}
                    </span>
                    <button type="button" wire:click="resendDelivery({{ $delivery->id }})"
                            style="font-size:.75rem; color:#1800ff; font-weight:600; background:none; border:none; cursor:pointer;">
                        {{ __('pages/webhooks.resend') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
