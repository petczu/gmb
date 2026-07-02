<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Protected report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.08); padding:28px; width:360px; max-width:92vw; text-align:center; }
        .card h1 { font-size:1.15rem; margin:.4rem 0 .2rem; }
        .card p { color:#6b7280; font-size:.9rem; margin:0 0 1.1rem; }
        input { width:100%; padding:.65rem .8rem; border:1px solid #d1d5db; border-radius:8px; font-size:.95rem; box-sizing:border-box; }
        button { width:100%; margin-top:.8rem; padding:.65rem; border:0; border-radius:8px; background:#2d19ec; color:#fff; font-weight:600; font-size:.95rem; cursor:pointer; }
        .err { color:#b91c1c; font-size:.85rem; margin-top:.6rem; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('reports.shared.unlock', $token) }}">
        @csrf
        <div style="font-size:1.8rem;">🔒</div>
        <h1>This report is password protected</h1>
        <p>Enter the password to view it.</p>
        <input type="password" name="password" placeholder="Password" autofocus required>
        <button type="submit">View report</button>
        @if (!empty($error))
            <div class="err">{{ $error }}</div>
        @endif
    </form>
</body>
</html>
