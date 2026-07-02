<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation unavailable</title>
    @include('partials.favicons')
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background:#f3f4f6; color:#1f2937; display:flex; min-height:100vh; align-items:center; justify-content:center; margin:0; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.08); padding:28px; width:400px; max-width:92vw; text-align:center; }
        .card h1 { font-size:1.15rem; margin:.4rem 0 .2rem; }
        .card p { color:#6b7280; font-size:.9rem; margin:0; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:1.8rem;">✉️</div>
        <h1>{{ $title ?? 'Invitation unavailable' }}</h1>
        <p>{{ $message ?? 'This invitation link is no longer valid. It may have expired or already been used. Please ask whoever invited you for a new one.' }}</p>
    </div>
</body>
</html>
