<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create workspace · Repunio</title>
    @include('partials.favicons')
    {{-- Alpine powers the workspace-switcher dropdown (same partial as the panel top bar). --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0; padding-top:64px; box-sizing:border-box; }
        .topbar { position:fixed; top:0; left:0; right:0; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; padding:0 1.25rem; z-index:40; }
        .topbar .brand { display:inline-flex; align-items:center; height:2rem; }
        .topbar .brand img, .topbar .brand svg { height:2rem !important; width:auto !important; }
        .topbar .end { display:flex; align-items:center; }
        .topbar .avatar { width:2rem; height:2rem; border-radius:9999px; object-fit:cover; display:block; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.08); padding:32px; width:420px; max-width:92vw; }
        .card h1 { font-size:1.25rem; margin:.4rem 0 .3rem; text-align:center; }
        .card p { color:#6b7280; font-size:.9rem; margin:0 0 1.4rem; text-align:center; }
        label { display:block; font-size:.82rem; font-weight:600; margin-bottom:.4rem; }
        input { width:100%; box-sizing:border-box; padding:.65rem .75rem; border:1px solid #e5e7eb; border-radius:9px; font-size:.95rem; }
        input:focus { outline:none; border-color:#1800ff; box-shadow:0 0 0 3px rgba(24,0,255,.12); }
        .err { color:#dc2626; font-size:.82rem; margin-top:.4rem; }
        .btn { display:block; width:100%; background:#1800ff; color:#fff; border:none; padding:.7rem; border-radius:9px; font-size:.95rem; font-weight:600; cursor:pointer; margin-top:1.25rem; }
        .back { display:block; text-align:center; margin-top:1rem; color:#9ca3af; font-size:.85rem; text-decoration:none; }
        .back:hover { color:#6b7280; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="{{ url('/') }}" class="brand">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>
        <div class="end">
            @include('filament.app.workspace-switcher')
            @php
                $avatarUrl = auth()->user()?->getFilamentAvatarUrl()
                    ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()?->name ?? 'U').'&color=FFFFFF&background=2d19ec';
            @endphp
            <a href="{{ url('/profile') }}" title="{{ auth()->user()?->name }}">
                <img class="avatar" src="{{ $avatarUrl }}" alt="{{ auth()->user()?->name }}">
            </a>
        </div>
    </header>

    <div class="card">
        <div style="font-size:1.8rem; text-align:center;">🏢</div>
        <h1>Create a workspace</h1>
        <p>A separate company with its own reviews, team and billing.</p>
        <form method="POST" action="{{ route('workspace.store') }}">
            @csrf
            <label for="name">Company name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="120" autofocus placeholder="Acme Agency">
            @error('name')<div class="err">{{ $message }}</div>@enderror
            <button type="submit" class="btn">Create workspace</button>
        </form>
        <a href="{{ url('/') }}" class="back">Cancel</a>
    </div>
</body>
</html>
