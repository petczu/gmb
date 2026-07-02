<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.approvals_pending.intro', ['count' => $count]) }}

<x-mail::button :url="$approvalsUrl">
{{ __('emails.approvals_pending.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
