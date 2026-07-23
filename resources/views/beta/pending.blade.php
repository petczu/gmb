<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\Locales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('beta.pending_title') }}</title>
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}">
    <style>
        * { box-sizing: border-box; margin: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            background: radial-gradient(120% 120% at 15% 0%, #eef0ff 0%, #f7f7fc 45%, #ffffff 100%);
            color: #111827;
            overflow: hidden;
        }
        .glow {
            position: fixed; right: -10rem; bottom: -10rem;
            width: 28rem; height: 28rem; border-radius: 999px;
            background: #2d19ec; opacity: .08; filter: blur(90px);
        }
        .logo { position: fixed; top: 1.75rem; left: 2rem; height: 2rem; display: inline-flex; }
        .wrap { position: relative; max-width: 30rem; text-align: center; }
        .badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(45, 25, 236, .08); border: 1px solid rgba(45, 25, 236, .12);
            border-radius: 999px; padding: .4rem 1rem; font-size: .8rem; font-weight: 600;
            color: #2d19ec; letter-spacing: .04em; text-transform: uppercase;
        }
        .badge .dot {
            width: .5rem; height: .5rem; border-radius: 999px; background: #f5b301;
            animation: pulse 1.6s ease-in-out infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
        h1 { font-size: clamp(1.6rem, 5vw, 2.2rem); font-weight: 800; margin: 1.2rem 0 .6rem; letter-spacing: -.02em; color: #111827; }
        p.sub { color: #6b7280; font-size: 1rem; line-height: 1.65; }
        p.sub strong { color: #111827; font-weight: 600; }
        .steps {
            margin: 2rem auto 0; max-width: 22rem; text-align: left;
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 1rem; padding: 1.1rem 1.2rem;
            box-shadow: 0 12px 30px rgba(17, 12, 60, .06);
        }
        .steps .row { display: flex; align-items: center; gap: .65rem; padding: .45rem 0; font-size: .86rem; color: #374151; }
        .steps .ok { color: #16a34a; }
        .steps .wait { color: #d97706; }
        .steps .later { color: #9ca3af; }
        .actions { display: flex; gap: .75rem; justify-content: center; margin-top: 2.2rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border-radius: 8px; padding: 8px 12px; border: 0;
            font-size: 14px; line-height: 20px; font-weight: 500; font-family: inherit;
            text-decoration: none; cursor: pointer; outline: none;
            transition: background-color .15s ease;
        }
        .btn-primary { background: rgb(24, 0, 255); color: #fff; }
        .btn-primary:hover { background: rgb(45, 25, 236); }
        .btn-ghost {
            background: #fff; color: #374151;
            box-shadow: inset 0 0 0 1px #d1d5db;
        }
        .btn-ghost:hover { background: #f9fafb; }
        .lang { position: fixed; top: 1.75rem; right: 2rem; z-index: 20; }
    </style>
</head>
<body>
    <div class="glow"></div>

    <a class="logo" href="{{ url('/') }}">
        {!! view('filament.logo', ['theme' => 'light'])->render() !!}
    </a>

    <div class="lang">
        @include('partials.locale-switch')
    </div>

    <div class="wrap">
        <div class="badge"><span class="dot"></span>{{ __('beta.pending_badge') }}</div>
        <h1>{{ __('beta.pending_headline') }}</h1>
        <p class="sub">{!! __('beta.pending_text', ['email' => '<strong>'.e($email).'</strong>']) !!}</p>

        <div class="steps">
            <div class="row"><span class="ok">✓</span>{{ __('beta.step_signed_up') }}</div>
            <div class="row"><span class="wait">●</span>{{ __('beta.step_review') }}</div>
            <div class="row"><span class="later">○</span>{{ __('beta.step_access') }}</div>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/') }}">{{ __('beta.check_status') }}</a>
            <form method="POST" action="{{ route('filament.app.auth.logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">{{ __('beta.logout') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
