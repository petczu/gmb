{{-- Right-to-left locales (Arabic) need the body mirrored; the wrapper keeps
     the branded Laravel mail shell untouched. --}}
@php($emailDirection = \App\Support\Locales::direction(app()->getLocale()))
<x-mail::message>
<div dir="{{ $emailDirection }}" @style(['text-align: right' => $emailDirection === 'rtl'])>
{!! $slotHtml !!}
</div>
</x-mail::message>
