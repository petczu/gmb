@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<img src="{{ rtrim((string) config('app.url'), '/') }}/logo/repunio-full-light.png" alt="{{ config('app.name', 'Repunio') }}" style="height: 38px; max-width: 220px;">
@endif
</a>
</td>
</tr>
