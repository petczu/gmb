<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.sync_restored.intro', ['account' => $accountName]) }}

<x-mail::button :url="$dashboardUrl">
{{ __('emails.sync_restored.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
