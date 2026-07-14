<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Invitation unavailable' }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'dark'])->render() !!}</a>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-muted">Invitation</span>
            <div class="icon-ring">✉️</div>
            <h1>{{ $title ?? 'Invitation unavailable' }}</h1>
            <p class="sub">{{ $message ?? 'This invitation link is no longer valid. It may have expired or already been used. Please ask whoever invited you for a new one.' }}</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">Go to Repunio</a>
            </div>
        </div>
    </div>
</body>
</html>
