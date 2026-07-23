<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('legal.accept_title') }} — Repunio</title>
    @include('partials.favicons')
    <style>
        body { margin:0; background:#f9fafb; color:#111827; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height:1.65; }
        .wrap { max-width:44rem; margin:0 auto; padding:5.5rem 1.5rem 4rem; }
        .corner-logo { position:fixed; top:1.25rem; left:1.5rem; z-index:20; display:inline-flex; align-items:center; height:2rem; }
        .corner-logo img, .corner-logo svg { height:2rem !important; width:auto !important; }
        h1 { font-size:1.9rem; margin:0 0 .25rem; }
        .lead { color:#6b7280; margin-bottom:1.5rem; }
        .doc { background:#fff; border:1px solid #e5e7eb; border-radius:.9rem; padding:1.5rem 1.75rem; max-height:26rem; overflow-y:auto; margin-bottom:1.5rem; }
        .doc h2 { font-size:1.05rem; margin:1.4rem 0 .4rem; }
        .doc h2:first-child { margin-top:0; }
        .doc p { margin:.5rem 0; color:#374151; font-size:.95rem; }
        .actions { display:flex; align-items:center; gap:1.25rem; }
        .btn { display:inline-block; background:#1800ff; color:#fff; border:0; border-radius:.6rem; padding:.75rem 1.6rem; font-weight:600; font-size:1rem; cursor:pointer; }
        .quiet { color:#6b7280; font-size:.9rem; text-decoration:underline; background:none; border:0; padding:0; cursor:pointer; }
        .lang { position:fixed; top:1.3rem; right:1.5rem; z-index:20; display:inline-flex; gap:.25rem; font-size:.85rem; }
        .lang a { padding:.2rem .5rem; border-radius:.4rem; color:#6b7280; text-decoration:none; }
        .lang a.on { background:#eef2ff; color:#1800ff; font-weight:600; }
    </style>
</head>
<body>
    <a class="corner-logo" href="{{ url('/') }}">{!! view('filament.logo', ['theme' => 'light'])->render() !!}</a>

    <nav class="lang" aria-label="Language">
        @foreach (['en' => 'EN', 'de' => 'DE'] as $code => $label)
            <a href="{{ route('locale.switch', $code) }}"
               class="{{ app()->getLocale() === $code ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="wrap">
        <h1>{{ __('legal.accept_title') }}</h1>
        <div class="lead">{{ __('legal.accept_lead') }}</div>

        <div class="doc">{!! $html !!}</div>

        <div class="actions">
            <form method="POST" action="{{ route('terms.accept') }}">
                @csrf
                <button type="submit" class="btn">{{ __('legal.accept_button') }}</button>
            </form>

            <form method="POST" action="{{ route('filament.app.auth.logout') }}">
                @csrf
                <button type="submit" class="quiet">{{ __('legal.accept_logout') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
