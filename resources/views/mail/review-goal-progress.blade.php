<x-mail::message>
{{ __('emails.greeting', ['name' => $name]) }}

@if ($variant === 'recap')
{{ __('emails.review_goal.intro_recap', ['month' => $data['month'], 'actual' => $data['total_actual'], 'goal' => $data['total_goal']]) }}

<x-mail::table>
| {{ __('emails.review_goal.col_location') }} | {{ __('emails.review_goal.col_goal') }} | {{ __('emails.review_goal.col_got') }} | {{ __('emails.review_goal.col_vs_goal') }} | {{ __('emails.review_goal.col_vs_prev') }} |
|:--- |:---:|:---:|:---:|:---:|
@foreach ($data['rows'] as $row)
| {{ $row['location'] }} | {{ $row['goal'] }} | {{ $row['actual'] }} | {{ $row['percent'] !== null ? $row['percent'].'%' : '—' }} | {{ $row['delta'] > 0 ? '+'.$row['delta'] : $row['delta'] }} |
@endforeach
</x-mail::table>
@else
{{ __('emails.review_goal.intro_mid_'.$data['status'], ['actual' => $data['total_actual'], 'goal' => $data['total_goal'], 'expected' => $data['total_expected']]) }}

<x-mail::table>
| {{ __('emails.review_goal.col_location') }} | {{ __('emails.review_goal.col_goal') }} | {{ __('emails.review_goal.col_so_far') }} | {{ __('emails.review_goal.col_projected') }} | {{ __('emails.review_goal.col_pace') }} |
|:--- |:---:|:---:|:---:|:---:|
@foreach ($data['rows'] as $row)
| {{ $row['location'] }} | {{ $row['goal'] }} | {{ $row['actual'] }} | {{ $row['projected'] }} | {{ __('emails.review_goal.status_'.$row['status']) }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="$reviewsUrl">
{{ __('emails.review_goal.cta') }}
</x-mail::button>

{{ __('emails.signoff') }}<br>
{{ __('emails.team') }}
</x-mail::message>
