<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $widget->setting('header_title') ?: ($widget->workspace?->name ?? 'Reviews') }}</title>
    <style>html,body{margin:0;padding:0;background:transparent}body{padding:2px}</style>
</head>
<body>
{!! $markup !!}
</body>
</html>
