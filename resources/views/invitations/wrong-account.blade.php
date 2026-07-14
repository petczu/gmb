<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('invitations.wrong_title') }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-muted">{{ __('invitations.badge') }}</span>
            {{-- Switch-accounts arrows --}}
            <div class="icon-ring">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
            </div>
            <h1>{{ __('invitations.wrong_title') }}</h1>
            {{-- The visitor is NOT the invitee — mask the invited address. --}}
            <p class="sub">{!! __('invitations.wrong_body', [
                'invited' => '<strong>'.e(\App\Services\Workspaces\InvitationAcceptor::maskEmail((string) $invitation->email)).'</strong>',
                'current' => '<strong>'.e($currentEmail).'</strong>',
            ]) !!}</p>
            <p class="sub" style="margin-top:.5rem;">{{ __('invitations.wrong_hint') }}</p>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url('/') }}">{{ __('invitations.back_to_app') }}</a>
                <form method="POST" action="{{ route('filament.app.auth.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost">{{ __('invitations.sign_out') }}</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
