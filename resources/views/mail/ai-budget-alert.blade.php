<x-mail::message>
# AI budget alert — {{ $percent }}% used

System-wide AI spend for **{{ $month }}** has reached **${{ number_format($spentUsd, 2) }}**
of the **${{ number_format($budgetUsd, 2) }}** monthly budget ({{ $percent }}%).

Nothing is blocked automatically — AI features keep working. Review usage per
workspace and feature on the admin AI usage page and adjust plans, caps or the
budget if needed.

<x-mail::button :url="url('/admin/ai-usage')">
Open AI usage
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
