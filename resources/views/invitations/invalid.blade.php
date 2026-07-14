<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('invitations.invalid_title') }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-muted">{{ __('invitations.badge') }}</span>
            {{-- Envelope with a "no longer valid" clock face --}}
            <div class="icon-ring">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h1>{{ $title ?? __('invitations.invalid_title') }}</h1>
            <p class="sub">{{ $message ?? __('invitations.invalid_body') }}</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">{{ __('invitations.go_to_app') }}</a>
            </div>
        </div>
    </div>
</body>
</html>
