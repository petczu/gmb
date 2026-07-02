<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.auto_recharge_failed.intro') }}

<x-mail::button :url="$billingUrl">
{{ __('emails.auto_recharge_failed.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
