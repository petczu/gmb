<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.review_anomaly.intro') }}

@foreach ($anomalies as $anomaly)
- **{{ $anomaly['location'] }}** — {{ __('emails.review_anomaly.'.$anomaly['type'], $anomaly['detail']) }}
@endforeach

<x-mail::button :url="$reviewsUrl">
{{ __('emails.review_anomaly.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
