<x-mail::message>
# Performance report, {{ $businessName }}

Here is your performance report for **{{ $periodLabel }}**.

{{ $summary }}

The full report is attached as a PDF.

<x-mail::button :url="config('app.url')">
Open dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
