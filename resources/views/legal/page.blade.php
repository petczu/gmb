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
        .wrap { max-width:44rem; margin:0 auto; padding:2.5rem 1.5rem 4rem; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:2rem; }
        .logo { display:inline-flex; align-items:center; height:30px; }
        .logo img, .logo svg { height:30px !important; width:auto !important; }
        h1 { font-size:1.9rem; margin:0 0 .25rem; }
        .updated { color:#9ca3af; font-size:.85rem; margin-bottom:2rem; }
        h2 { font-size:1.15rem; margin:2rem 0 .5rem; }
        p { margin:.5rem 0; color:#374151; }
        a { color:#1800ff; }
        .back { display:inline-block; margin-top:2.5rem; font-size:.9rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <a href="{{ url('/') }}"><span class="logo">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</span></a>
            <div class="lang">
                @include('partials.locale-switcher')
            </div>
        </div>

        <h1>{{ __('legal.'.$page.'.title') }}</h1>
        <div class="updated">{{ __('legal.updated', ['date' => __('legal.updated_date')]) }}</div>

        @foreach ($sections as $section)
            @if (! empty($section['h']))<h2>{{ $section['h'] }}</h2>@endif
            <p>{{ $section['p'] }}</p>
        @endforeach

        <a class="back" href="{{ url('/') }}">← {{ __('legal.back') }}</a>
    </div>
</body>
</html>
