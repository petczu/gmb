<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.subscription_canceled.intro', ['date' => $endsOn]) }}

{{ __('emails.subscription_canceled.note') }}

<x-mail::button :url="$billingUrl">
{{ __('emails.subscription_canceled.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
