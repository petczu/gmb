<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation for {{ $invitation->email }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'dark'])->render() !!}</a>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-muted">Invitation</span>
            <div class="icon-ring">🔀</div>
            <h1>This invite is for someone else</h1>
            <p class="sub">It was sent to <strong>{{ $invitation->email }}</strong>, but you're signed in as <strong>{{ $currentEmail }}</strong>.</p>
            <p class="sub" style="margin-top:.5rem;">Forward the link to them, or sign out and sign in with that email to accept it yourself.</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">Back to the app</a>
                <form method="POST" action="{{ route('filament.app.auth.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
