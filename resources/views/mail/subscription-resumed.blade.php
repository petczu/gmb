<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.subscription_resumed.intro') }}

<x-mail::button :url="$billingUrl">
{{ __('emails.subscription_resumed.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
