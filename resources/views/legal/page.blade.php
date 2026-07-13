@php
    $sections = trans('legal.'.$page.'.sections');
    $sections = is_array($sections) ? $sections : [];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index,follow">
    <title>{{ __('legal.'.$page.'.title') }} — Repunio</title>
    @include('partials.favicons')
    <style>
        body { margin:0; background:#f9fafb; color:#111827; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height:1.65; }
        .wrap { max-width:44rem; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
        .corner-logo { position:fixed; top:1.25rem; left:1.5rem; z-index:20; display:inline-flex; align-items:center; height:2rem; }
        .corner-logo img, .corner-logo svg { height:2rem !important; width:auto !important; }
        .corner-lang { position:fixed; top:1.25rem; right:1.5rem; z-index:20; }
        h1 { font-size:1.9rem; margin:0 0 .25rem; }
        .updated { color:#9ca3af; font-size:.85rem; margin-bottom:2rem; }
        h2 { font-size:1.15rem; margin:2rem 0 .5rem; }
        p { margin:.5rem 0; color:#374151; }
        a { color:#1800ff; }
        .back { display:inline-block; margin-top:2.5rem; font-size:.9rem; }
    </style>
</head>
<body>
    <a class="corner-logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>
    <div class="corner-lang">
        @include('partials.locale-switch')
    </div>

    <div class="wrap">
        <h1>{{ __('legal.'.$page.'.title') }}</h1>
        <div class="updated">{{ __('legal.updated', ['date' => __('legal.updated_date')]) }}</div>

        @if (! empty($bodyHtml))
            {!! $bodyHtml !!}
        @else
            @foreach ($sections as $section)
                @if (! empty($section['h']))<h2>{{ $section['h'] }}</h2>@endif
                <p>{{ $section['p'] }}</p>
            @endforeach
        @endif

        <a class="back" href="{{ url('/') }}">← {{ __('legal.back') }}</a>
    </div>
</body>
</html>
