<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.negative_review.intro', ['business' => $businessName]) }}

<x-mail::table>
| {{ __('emails.negative_review.col_author') }} | {{ __('emails.negative_review.col_rating') }} | {{ __('emails.negative_review.col_review') }} |
|:---|:---:|:---|
| {{ $authorName }} | {{ str_repeat('★', $rating) }} | {{ $snippet }} |
</x-mail::table>

<x-mail::button :url="$reviewsUrl">
{{ __('emails.negative_review.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
