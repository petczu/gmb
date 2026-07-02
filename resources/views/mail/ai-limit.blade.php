<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.ai_limit.intro', ['plan' => $plan]) }}

<x-mail::button :url="$plansUrl">
{{ __('emails.ai_limit.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
