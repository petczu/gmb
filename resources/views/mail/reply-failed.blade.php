<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.reply_failed.intro', ['business' => $businessName]) }}

<x-mail::table>
| {{ __('emails.reply_failed.col_author') }} | {{ __('emails.reply_failed.col_review') }} |
|:---|:---|
| {{ $authorName }} | {{ $snippet }} |
</x-mail::table>

{{ __('emails.reply_failed.detail') }}

<x-mail::button :url="$reviewsUrl">
{{ __('emails.reply_failed.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
