<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.payment_succeeded.intro', ['amount' => $amount]) }}

<x-mail::button :url="$billingUrl">
{{ __('emails.payment_succeeded.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
