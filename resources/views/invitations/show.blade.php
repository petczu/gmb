<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join {{ $invitation->workspace?->name }}</title>
    @include('partials.favicons')
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.08); padding:32px; width:420px; max-width:92vw; text-align:center; }
        .card h1 { font-size:1.25rem; margin:.6rem 0 .4rem; }
        .card p { color:#6b7280; font-size:.92rem; margin:0 0 1.4rem; }
        .btn { display:inline-block; background:#111827; color:#fff; text-decoration:none; padding:11px 22px; border-radius:9px; font-size:.95rem; border:none; cursor:pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:1.8rem;">🤝</div>
        <h1>Join {{ $invitation->workspace?->name }}</h1>
        <p>You've been invited to join <strong>{{ $invitation->workspace?->name }}</strong> on Repunio as {{ \Illuminate\Support\Str::headline($invitation->role) }}.</p>
        <form method="POST" action="{{ route('invite.accept', $invitation->token) }}">
            @csrf
            <button type="submit" class="btn">Accept invitation</button>
        </form>
    </div>
</body>
</html>
