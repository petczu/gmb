<x-mail::message>
{{ __('emails.invite.greeting') }}

{{ __('emails.invite.intro', ['inviter' => $inviterName, 'workspace' => $workspaceName, 'role' => $role]) }}

<x-mail::button :url="$acceptUrl">
{{ __('emails.invite.cta') }}
</x-mail::button>

{{ __('emails.invite.note') }}

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
