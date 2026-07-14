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
                        <x-filament::link tag="button" icon="heroicon-o-arrow-path" size="sm"
                            wire:click="resendInvitation({{ $invitation->id }})" wire:loading.attr="disabled">
                            {{ __('pages/team.invite_resend') }}
                        </x-filament::link>
                        <x-filament::link tag="button" color="danger" icon="heroicon-o-trash" size="sm"
                            wire:click="confirmRevokeInvitation({{ $invitation->id }})">
                            {{ __('pages/team.invite_revoke') }}
                        </x-filament::link>
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
