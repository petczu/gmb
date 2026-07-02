<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.trial_ending.intro', ['date' => $date]) }}

<x-mail::button :url="$billingUrl">
{{ __('emails.trial_ending.cta') }}
</x-mail::button>

{{ __('emails.trial_ending.note') }}

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
