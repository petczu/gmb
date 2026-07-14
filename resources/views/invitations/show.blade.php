<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $workspaceName = $invitation->workspace?->name;
        $role = (string) $invitation->role;
        $roleLabel = trans()->has("invitations.roles.{$role}")
            ? __("invitations.roles.{$role}")
            : \Illuminate\Support\Str::headline($role);
    @endphp
    <title>{{ __('invitations.join_title', ['workspace' => $workspaceName]) }}</title>
    @include('partials.favicons')
    @include('invitations._styles')
</head>
<body>
    <div class="glow"></div>
    <a class="logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>

    <div class="lang">
        @include('partials.locale-switch')
    </div>

    <div class="wrap">
        <div class="card">
            <span class="badge badge-brand">{{ __('invitations.youre_invited') }}</span>
            <div class="ws-avatar">{{ \Illuminate\Support\Str::of($workspaceName ?? 'R')->substr(0, 1)->upper() }}</div>
            <h1>{{ __('invitations.join_title', ['workspace' => $workspaceName]) }}</h1>
            <p class="sub">{!! __('invitations.join_body', [
                'workspace' => '<strong>'.e($workspaceName).'</strong>',
                'role' => '<span class="role">'.e($roleLabel).'</span>',
            ]) !!}</p>

            <form method="POST" action="{{ route('invite.accept', $invitation->token) }}" class="actions">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('invitations.accept_button') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
