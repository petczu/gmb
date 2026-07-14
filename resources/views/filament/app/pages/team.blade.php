<x-filament-panels::page>
    {{ $this->table }}

    @php $pending = $this->pendingInvitations(); @endphp

    @if ($pending->isNotEmpty())
        {{-- Invitations that were sent but not accepted yet: resend or revoke. --}}
        <style>
            .tm-pending { border: 1px solid rgb(0 0 0 / .08); border-radius: .75rem; background: #fff; overflow: hidden; }
            .dark .tm-pending { background: #18181b; border-color: rgb(255 255 255 / .1); }
            .tm-pending .head { padding: .85rem 1rem .2rem; font-weight: 700; font-size: .95rem; }
            .tm-pending .sub { padding: 0 1rem .6rem; font-size: .8rem; color: #6b7280; }
            .tm-row { display: flex; align-items: center; gap: 1rem; padding: .7rem 1rem; border-top: 1px solid rgb(0 0 0 / .06); font-size: .875rem; flex-wrap: wrap; }
            .dark .tm-row { border-color: rgb(255 255 255 / .08); }
            .tm-row .email { flex: 1 1 14rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
            .tm-row .role { font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px; background: rgb(0 0 0 / .06); }
            .dark .tm-row .role { background: rgb(255 255 255 / .1); }
            .tm-row .when { flex: 0 1 auto; color: #6b7280; font-size: .78rem; }
            .tm-row .when.expired { color: #dc2626; }
            .tm-row .actions { margin-left: auto; display: inline-flex; align-items: center; gap: .9rem; }
            .tm-row .actions button { border: 0; background: transparent; cursor: pointer; font-size: .82rem; font-weight: 600; display: inline-flex; align-items: center; gap: .3rem; padding: 0; }
            .tm-row .actions .resend { color: #2d19ec; }
            .dark .tm-row .actions .resend { color: #a5b4fc; }
            .tm-row .actions .revoke { color: #dc2626; }
        </style>

        <div class="tm-pending">
            <div class="head">{{ __('pages/team.pending_title') }}</div>
            <div class="sub">{{ __('pages/team.pending_hint') }}</div>

            @foreach ($pending as $invitation)
                <div class="tm-row" wire:key="invite-{{ $invitation->id }}">
                    <span class="email">{{ $invitation->email }}</span>
                    <span class="role">{{ $invitation->role }}</span>
                    <span class="when {{ $invitation->isExpired() ? 'expired' : '' }}">
                        @if ($invitation->isExpired())
                            {{ __('pages/team.pending_expired') }}
                        @else
                            {{ __('pages/team.pending_sent', ['ago' => $invitation->created_at->diffForHumans()]) }}
                        @endif
                    </span>
                    <span class="actions">
                        <button type="button" class="resend" wire:click="resendInvitation({{ $invitation->id }})" wire:loading.attr="disabled">
                            <svg style="width:.95rem; height:.95rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            {{ __('pages/team.invite_resend') }}
                        </button>
                        <button type="button" class="revoke" wire:click="confirmRevokeInvitation({{ $invitation->id }})">
                            <svg style="width:.95rem; height:.95rem;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            {{ __('pages/team.invite_revoke') }}
                        </button>
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
