<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

{{ __('emails.new_reviews.intro', ['count' => $count, 'location' => $locationName]) }}

@if (count($samples) > 0)
<x-mail::table>
| {{ __('emails.new_reviews.col_author') }} | {{ __('emails.new_reviews.col_rating') }} | {{ __('emails.new_reviews.col_location') }} | {{ __('emails.new_reviews.col_review') }} |
|:---|:---:|:---|:---|
@foreach ($samples as $sample)
| {{ $sample['author'] }} | {{ str_repeat('★', (int) $sample['rating']) }} | {{ $sample['location'] ?? '—' }} | {{ $sample['snippet'] }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="$reviewsUrl">
{{ __('emails.new_reviews.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
