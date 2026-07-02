<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.welcome.intro') }}

<x-mail::button :url="config('app.url').'/'">
{{ __('emails.welcome.cta') }}
</x-mail::button>

{{ __('emails.welcome.next') }}

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
