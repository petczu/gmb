{{-- Shown at the top of the registration form when the visitor arrived from an
     invitation link. The email field below is prefilled + locked to the invited
     address, so this explains why. --}}
<div style="display: flex; gap: .75rem; align-items: flex-start; padding: .85rem 1rem; border-radius: .75rem; background: rgb(24 0 255 / .06); border: 1px solid rgb(24 0 255 / .15);">
    <div style="flex: 0 0 auto; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: rgb(24 0 255 / .12); font-size: 1rem;">✉️</div>
    <div style="font-size: .875rem; line-height: 1.5;">
        <div style="font-weight: 600;">
            {{ filled($workspace)
                ? __('auth.invite_join_title', ['workspace' => $workspace])
                : __('auth.invite_join_title_generic') }}
        </div>
        <div style="opacity: .75; margin-top: .15rem;">
            {{ $hint ?? __('auth.invite_email_locked', ['email' => $email]) }}
        </div>
    </div>
</div>
