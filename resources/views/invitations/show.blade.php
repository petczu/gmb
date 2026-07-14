<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join {{ $invitation->workspace?->name }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'dark'])->render() !!}</a>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-brand">✉️ You're invited</span>
            <div class="ws-avatar">{{ \Illuminate\Support\Str::of($invitation->workspace?->name ?? 'R')->substr(0, 1)->upper() }}</div>
            <h1>Join {{ $invitation->workspace?->name }}</h1>
            <p class="sub">You've been invited to <strong>{{ $invitation->workspace?->name }}</strong> on Repunio as
                <span class="role">{{ \Illuminate\Support\Str::headline($invitation->role) }}</span>.</p>

            <form method="POST" action="{{ route('invite.accept', $invitation->token) }}" class="actions">
                @csrf
                <button type="submit" class="btn btn-primary">Accept &amp; join</button>
            </form>
        </div>
    </div>
</body>
</html>
