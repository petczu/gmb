<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.account_disconnected.intro', ['account' => $accountName]) }}

{{ __('emails.account_disconnected.detail') }}

<x-mail::button :url="$locationsUrl">
{{ __('emails.account_disconnected.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
