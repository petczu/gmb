<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.payment_failed.intro', ['days' => $days]) }}

<x-mail::button :url="$billingUrl" color="error">
{{ __('emails.payment_failed.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
