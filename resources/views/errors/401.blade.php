<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\Locales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('errors.401_title') }}</title>
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
        .code {
            font-size: clamp(5rem, 16vw, 8.5rem); font-weight: 800; line-height: 1;
            background: linear-gradient(135deg, #111827 30%, #2d19ec 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
            letter-spacing: -.03em;
        }
        h1 { font-size: 1.5rem; font-weight: 700; margin: .9rem 0 .5rem; }
        p.sub { color: #6b7280; font-size: 1rem; line-height: 1.6; }
        .review {
            margin: 2rem auto 0; max-width: 21rem; text-align: left;
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 1rem; padding: 1rem 1.1rem;
            transform: rotate(-1.5deg); box-shadow: 0 16px 40px rgba(17, 12, 60, .1);
        }
        .review .who { display: flex; align-items: center; gap: .55rem; }
        .review .ava {
            width: 1.9rem; height: 1.9rem; border-radius: 999px; background: #7c6cf0;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; color: #fff;
        }
        .review .name { font-size: .82rem; font-weight: 600; }
        .review .stars { color: #f5b301; font-size: .78rem; letter-spacing: .1em; }
        .review .text { color: #4b5563; font-size: .82rem; line-height: 1.5; margin: .6rem 0 .7rem; font-style: italic; }
        .review .reply {
            display: flex; align-items: center; gap: .45rem;
            border-top: 1px solid #e5e7eb; padding-top: .65rem;
            color: #6b7280; font-size: .76rem;
        }
        .actions { display: flex; gap: .75rem; justify-content: center; margin-top: 2.2rem; flex-wrap: wrap; }
        /* Matches the app's fi-btn (size md) computed styles exactly:
           14px/500, 8px 12px padding, 8px radius, no shadow, 6px gap. */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border-radius: 8px; padding: 8px 12px;
            font-size: 14px; line-height: 20px; font-weight: 500;
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
    </style>
</head>
<body>
    <div class="glow"></div>

    <a class="logo" href="{{ url('/') }}">
        {!! view('filament.logo', ['theme' => 'light'])->render() !!}
    </a>

    <div class="wrap">
        <div class="code">401</div>
        <h1>{{ __('errors.401_headline') }}</h1>
        <p class="sub">{{ __('errors.401_text') }}</p>

        <div class="review">
            <div class="who">
                <span class="ava">RC</span>
                <div>
                    <div class="name">{{ __('errors.401_reviewer') }}</div>
                    <div class="stars">★★★★☆</div>
                </div>
            </div>
            <div class="text">{{ __('errors.401_review') }}</div>
            <div class="reply">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2d19ec" style="width:.95rem; height:.95rem;"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5Z" clip-rule="evenodd"/></svg>
                {{ __('errors.401_reply') }}
            </div>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/login') }}">{{ __('errors.401_signin') }}</a>
            <a class="btn btn-ghost" href="{{ url('/') }}">{{ __('errors.401_home') }}</a>
        </div>
    </div>
</body>
</html>
