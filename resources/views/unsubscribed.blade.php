<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('emails.unsubscribed_title') }} — Repunio</title>
    @include('partials.favicons')
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f7f7f9; color:#111827; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.08); padding:2.2rem; width:26rem; max-width:92vw; text-align:center; }
        h1 { font-size:1.25rem; margin:.4rem 0 .5rem; }
        p { color:#6b7280; font-size:.92rem; line-height:1.6; margin:0; }
        a { color:#2d19ec; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:1.8rem;">✅</div>
        <h1>{{ __('emails.unsubscribed_title') }}</h1>
        <p>{!! __('emails.unsubscribed_body', ['link' => '<a href="'.url('/profile').'">'.e(__('emails.unsubscribed_profile')).'</a>']) !!}</p>
    </div>
</body>
</html>
